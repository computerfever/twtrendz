<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Acelle\Model\Campaign;
use Acelle\Model\Email;
use Acelle\Model\Subscriber;
use Acelle\Model\SendingServer;
use Acelle\Model\Subscription;
use Acelle\Library\Exception\RateLimitExceeded;
use Acelle\Library\Exception\RateLimitReservedByAnotherFileSystem;
use Acelle\Library\Exception\OutOfCredits;
use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log as LaravelLog;
use Acelle\Library\RouletteWheel;

use function Acelle\Helpers\execute_with_limits;

class SendMessage implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    // @important: set the "retry_after" setting in config/queue.php to a value that is greater than $timeout;
    // Otherse, the job shall be released and attempted again, resulting in error like:
    // "[Job] has been attempted too many times or run too long. The job may have previously timed out."

    // @important: https://laravel.com/docs/8.x/queues#failing-on-timeout
    // Sometimes, IO blocking processes such as sockets or outgoing HTTP connections
    // may not ***RESPECT*** your specified timeout. Therefore, when using these features,
    // you should always attempt to specify a timeout using their APIs as well.
    // For example, when using Guzzle, you should always specify a connection and request timeout value.
    public $timeout = 900; // do not actually show timeout to user, wait for auto resume campaign instead
    public $maxExceptions = 1; // This is required if retryUntil is used, otherwise, the default value is 255
    public $failOnTimeout = true;

    // $tries is no longer needed (or effective) due to the retryUntil() method
    // public $tries = 1;

    protected $subscriber;
    protected $servers;
    protected $campaign;
    protected $subscription;
    protected $triggerId;
    protected $stopOnError = false;

    protected $selectedServer;
    protected $msgId;
    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($campaign, Subscriber $subscriber, RouletteWheel $servers, Subscription $subscription = null, $triggerId = null)
    {
        $this->campaign = $campaign;
        $this->subscriber = $subscriber;
        $this->servers = $servers;
        $this->subscription = $subscription;
        $this->triggerId = $triggerId;
        $this->customer = $this->campaign->customer;
    }

    public function setStopOnError($value)
    {
        if (!is_bool($value)) {
            throw new Exception('Parameter passed to setStopOnError must be bool');
        }

        $this->stopOnError = $value;
    }

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        // @important: remember that messages might be released over and over
        // if there is any limit setting in place
        // As a result, it is just save to have it retry virtually forever
        return now()->addDays(30);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Might cause problems with batch
        // Set default connection
        // $this->customer->setUserDbConnection();

        // Remember that this job may not belong to a batch
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        // Last update recording should go here
        // before any other tasks (to prevent IO blocking tasks)
        // In case we need to clean up pending jobs, at least we know that last job start time
        // Reduce the posibility of killing a newly started (and still running) job
        $this->campaign->debug(function ($info) {
            // Record last activity, no matter it is a successful delivery or exception
            // This information is useful when we want to audit delivery processes
            // i.e. when we can to automatically restart dead jobs for example
            $info['last_activity_at'] = Carbon::now()->toString();

            // Must return;
            return $info;
        });

        $this->send();
    }

    // Use a dedicated method with no dependency for easy testing
    public function send($exceptionCallback = null)
    {
        try {

            // debug
            $startAt = Carbon::now()->getTimestampMs();
            $email = $this->subscriber->getEmail(); // important: $email must be here, in case code in Exception block needs it

            $logger = $this->campaign->logger();
            $serverRateLimitTracker = null;

            // Rate limit trackers
            // Here we have 2 rate trackers
            // 1. Sending server sending rate tracker with 1 or more limits.
            // 2. Subscription (plan) sending speed limits with 1 or more limits.
            $rateTrackers = [];
            $creditTrackers = [];

            if (!is_null($this->subscription)) {
                $rateTrackers[] = $this->subscription->getSendEmailRateTracker();
                $creditTrackers[] = $this->subscription->getSendEmailCreditTracker();
            }

            // DEBUG
            $finishPreparingAt = Carbon::now()->getTimestampMs();
            $finishDeliveryAt = null;
            $getLockAt = null;
            // END DEBUG

            $startGettingLock = Carbon::now()->getTimestampMs();
            execute_with_limits($rateTrackers, $this->servers, $creditTrackers, function ($selectedServer) use ($startAt, $logger, $email, &$finishDeliveryAt, &$getLockAt, $startGettingLock) {

                $getLockAt = Carbon::now()->getTimestampMs();
                $getLockDiff = ($getLockAt - $startAt) / 1000;
                $lockWaitingTime = ($getLockAt - $startGettingLock) / 1000;
                $logger->info(sprintf('Got lock for %s after "%s" seconds (lock waiting time %s)', $email, $getLockDiff, $lockWaitingTime));

                // Assign it here in order to retrieve it elsewhere
                $this->selectedServer = $selectedServer;
                $logger->info(sprintf('Server %s (id %s) selected', $this->selectedServer->name, $this->selectedServer->id));

                // Prepare the email message to send
                // In case of an invalid email, an exception will arise at: Swift_Mime_SimpleMessage->setTo(...)
                list($message, $msgId) = $this->campaign->prepareEmail($this->subscriber, $this->selectedServer, $fromCache = true);

                // Assign it here in order to retrieve it elsewhere
                $this->msgId = $msgId;

                if (!$this->subscriber->isSubscribed()) {
                    // @important: do not throw an exception here
                    // For this particular case (contact becomes inactive right before delivery), just silently
                    // record a failed delivery in delivery log, do not interrupt the whole campaign
                    $sent = [
                        'error' => trans('messages.delivery.error.subscriber_not_active', [ 'status' => $this->subscriber->status ]),
                        'status' => 'failed',
                    ];
                } else {

                    /*
                     *
                     * It would be a flaw to evaluate DELAY FLAG here. Why?
                     *
                     * Suppose job-100 passes all rate checks, proceeding with SEND...
                     *
                     * Then job-101 comes, it does not pass one of the rate checks and proceeds with setting a DELAY FLAG
                     * The problem: what if job-101 finish setting the delay flags even before job-100 actually SEND?
                     * ==> job-100 will unexpectedly be delayed although it has passed the rate checks
                     *
                     * The root cause here is: in a distributed setup, there is no guarantee that job-100 finishes SEND
                     * before job-101 sets the DELAY flag, even though job-001 comes first!
                     *
                     * So, DELAY FLAG checking must be executed before RATE checking (if it is ever needed)
                     *
                     * Illustration of the concurrency issue:
                     *
                     *     job-100 -------------------- PASS RATE CHECKS ----------------------------------------- CHECK DELAY FLAG, BREAK!!! ----------- SEND
                     *                                                                                           /
                     *                                                                                          /
                     *     job-101 ----------------------------FAILED RATE CHECKS --------- SET DELAY FLAG -----
                     *
                     */

                    /*
                    $delayFlag = $this->campaign->checkDelayFlag();
                    if ($delayFlag == true) {
                        // just finish the task
                        $logger->info(sprintf("Delayed [%s] due to rate limit (RIGHT BEFORE SENDING)", $email));
                        return;
                    }
                    */

                    if (config('custom.dryrun') || $this->campaign->name == '_DRYRUN') {
                        $sent = $this->selectedServer->dryrun($message);
                    } else {
                        $sent = $this->selectedServer->send($message);
                    }
                }

                $finishDeliveryAt = Carbon::now()->getTimestampMs();

                $logger->info(sprintf('Sent to %s', $email));

                // Log successful shot
                $this->campaign->trackMessage($sent, $this->subscriber, $this->selectedServer, $this->msgId, $this->triggerId);

                // Callback after a job is done
                $this->afterSuccess();

                // Done, written to tracking_logs table
                $logger->info(sprintf('Done with %s [Server "%s"]', $email, $this->selectedServer->name));
            });

            // Debug
            $now = Carbon::now(); // OK DONE ALL
            $finishAt = $now->getTimestampMs();

            $this->campaign->debug(function ($info) use ($startAt, $now, $finishAt, $finishPreparingAt, $finishDeliveryAt, $getLockAt) {
                $diff = ($finishAt - $startAt) / 1000;
                $avg = $info['send_message_avg_time'];
                if (is_null($avg)) {
                    $info['send_message_avg_time'] = $diff;
                } else {
                    $info['send_message_avg_time'] = ($avg * $info['send_message_count'] + $diff) / ($info['send_message_count'] + 1);
                }

                $prepareDiff = ($finishPreparingAt - $startAt) / 1000;
                $prepareAvg = $info['send_message_prepare_avg_time'] ?? null;
                if (is_null($prepareAvg)) {
                    $info['send_message_prepare_avg_time'] = $prepareDiff;
                } else {
                    $info['send_message_prepare_avg_time'] = ($prepareAvg * $info['send_message_count'] + $prepareDiff) / ($info['send_message_count'] + 1);
                }

                $getLockDiff = ($getLockAt - $startAt) / 1000;
                $getLockAvg = $info['send_message_lock_avg_time'] ?? null;
                if (is_null($getLockAvg)) {
                    $info['send_message_lock_avg_time'] = $getLockDiff;
                } else {
                    $info['send_message_lock_avg_time'] = ($getLockAvg * $info['send_message_count'] + $getLockDiff) / ($info['send_message_count'] + 1);
                }

                $deliveryDiff = ($finishDeliveryAt - $startAt) / 1000;
                $deliveryAvg = $info['send_message_delivery_avg_time'] ?? null;
                if (is_null($deliveryAvg)) {
                    $info['send_message_delivery_avg_time'] = $deliveryDiff;
                } else {
                    $info['send_message_delivery_avg_time'] = ($deliveryAvg * $info['send_message_count'] + $deliveryDiff) / ($info['send_message_count'] + 1);
                }

                // COUNT MESSAGE. IMPORTANT: it must go after the other calculation
                $info['send_message_count'] = $info['send_message_count'] + 1;

                if (is_null($info['send_message_min_time']) || $diff < $info['send_message_min_time']) {
                    $info['send_message_min_time'] = $diff;
                }

                if (is_null($info['send_message_max_time']) || $diff > $info['send_message_max_time']) {
                    $info['send_message_max_time'] = $diff;
                }

                $info['last_message_sent_at'] = $now->toString();
                $campaignStartAt = $info['start_at'];
                $timeSinceCampaignStart = $now->diffInSeconds(Carbon::parse($campaignStartAt));

                // In case it is too fast, avoid DivisionByZero
                $info['total_time'] = ($timeSinceCampaignStart == 0) ? 1 : $timeSinceCampaignStart;
                $info['messages_sent_per_second'] = $info['send_message_count'] / $info['total_time'];

                return $info;
            });
        } catch (RateLimitReservedByAnotherFileSystem $ex) {
            // This is for Dynamic Rate Limit tracking only
            $logger->info(sprintf("Delay [%s] due to rate limit RESERVATION: %s. Release, waiting for a process from the filesystem that owns the reservation", $email, $ex->getMessage()));

            // Simply release the job back to queue, hopefully it will picked up again by a process of the filesystem that owns the reservation.
            // So "attempts" will be >0
            $this->release(1);

            return;
        } catch (RateLimitExceeded $ex) {
            if (!is_null($exceptionCallback)) {
                return $exceptionCallback($ex);
            }

            if ($this->batch()) {
                $lockKey = "campaign-delay-flag-lock-{$this->campaign->uid}";
                with_cache_lock($lockKey, function () use ($ex, $logger, $email, $rateTrackers) {
                    // Use DELAY FLAG to make sure that only ONE DelayJob is created
                    $delayFlag = $this->campaign->checkDelayFlag();

                    if ($delayFlag == true) {
                        // just finish the task
                        $logger->info(sprintf("Quit [%s] due to rate limit: %s", $email, $ex->getMessage()));
                        return;
                    } else {
                        // Releease the job, have it tried again later on, after 1 minutes

                        $delayInSeconds = 60; // reservation stategy, so 60 seconds is good enough

                        $logger->warning(sprintf("DELAY!!! [%s], dispatch WAITING job (%s seconds): %s", $email, $delayInSeconds, $ex->getMessage()));

                        // set delay flag to true
                        $this->campaign->setDelayFlag(true);

                        // Important: here we have
                        // - Subscription/plan rate limit
                        // - All possible servers rate limit
                        // Delay job will evaluate all these conditions before resuming the campaign

                        // Reset is required
                        $this->servers->reset();

                        $delay = new Delay($delayInSeconds, $this->campaign, $rateTrackers);
                        $this->batch()->add($delay);

                        $this->campaign->debug(function ($info) use ($ex) {
                            // @todo: consider making it an interface, rather than access the .delay_note attribute directly like this
                            $info['delay_note'] = sprintf("Speed limit hit: %s", $ex->getMessage());

                            // Must return;
                            return $info;
                        });
                    }
                });

            } else {
                // Single queue, no batch
                $this->handleRateLimitExceeded($email, $ex);
            }

        } catch (Throwable $ex) {
            if (!is_null($exceptionCallback)) {
                return $exceptionCallback($ex);
            }

            $message = sprintf("Error sending to [%s]. Error: %s", $email, $ex->getMessage().": \n".$ex->getTraceAsString());
            $logger->error($message);

            $handled = $this->handleUnknownException($ex);

            // In case of these exceptions, stop campaign immediately even if stopOnError is currently false
            // This is helpful in certain cases: for example, when credits runs out, then it does not make sense to keep sending (and failing)
            $forceEndCampaignExceptions = [
                OutOfCredits::class,
                // Other "end-game" exception like "SendingServer out of credits, etc."
            ];

            $forceEndCampaign = in_array(get_class($ex), $forceEndCampaignExceptions);

            // There are 2 options here
            // Option 1: throw an exception and show it to users as the campaign status
            //     throw new Exception($message);
            // Option 2: just skip the error, log it and proceed with the next subscriber

            if ($handled) {
                $this->campaign->trackMessage(['status' => 'failed', 'error' => $message], $this->subscriber, $this->selectedServer, $this->msgId, $this->triggerId);
            } elseif ($this->stopOnError || $forceEndCampaign || is_null($this->selectedServer)) {
                throw new Exception($message);
            } else {
                $this->campaign->trackMessage(['status' => 'failed', 'error' => $message], $this->subscriber, $this->selectedServer, $this->msgId, $this->triggerId);
            }
        }
    }

    public function afterSuccess()
    {
        return;
    }

    public function handleUnknownException($ex)
    {
        return;
    }

    public function handleRateLimitExceeded($email, RateLimitExceeded $ex)
    {
        // Nothing here
        // For no-batch case only
    }
}
