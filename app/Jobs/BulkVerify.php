<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Acelle\Library\Traits\Trackable;
use Acelle\Library\Exception\RateLimitExceeded;
use Acelle\Model\Subscription;
use Acelle\Model\MailList;
use Acelle\Model\EmailVerificationServer;
use Exception;

class BulkVerify implements ShouldQueue
{
    use Trackable;
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 900;
    public $maxExceptions = 1; // This is required if retryUntil is used, otherwise, the default value is 255
    public $failOnTimeout = true;

    public const MIN_PER_BATCH = 200;
    public const MAX_PER_BATCH = 1000;
    public const NUMBER_OF_BATCHES = 10;

    // Allow it to try MANY times (in case of rate limit exceed)
    // public $tries = 1;

    protected $server;
    protected $mailList;
    protected $subscription;
    protected $customer;

    public function __construct(MailList $mailList, EmailVerificationServer $server, Subscription $subscription)
    {
        $this->mailList = $mailList;
        $this->server = $server;
        $this->subscription = $subscription;

        $this->customer = $this->mailList->customer;
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
        return now()->addDays(7);
    }

    public function handle()
    {
        $this->customer->setUserDbConnection();

        if ($this->batch()->cancelled()) {
            $this->mailList->logger()->warning('Job cancelled');
            return;
        }

        $msg = $this->monitor->getJsonData()['message'] ?? null;
        if (is_null($msg)) {
            // First run of BulkVerify
            $this->monitor->updateJsonData([
                'percentage' => round($this->mailList->getVerifiedSubscribersPercentage() * 100, 2),
                'message' => trans('messages.list.verification.progress.started'),
            ]);
        }

        $logger = $this->mailList->logger();
        $logger->info('+ Start bulk verifying');

        $creditTracker = $this->subscription->getVerifyEmailCreditTracker();
        if ($creditTracker->isZero()) {
            $logger->warning('+ Verification credits exceeded!');
            throw new Exception('Verification credits exceeded!');
        }

        $serviceCredits = $this->server->getServiceClient()->getCredits();
        if ($serviceCredits == 0) {
            $logger->warning('User has run out of service credit');
            throw new Exception('User  run out of service credit');
        }

        $subscribersQuery = $this->mailList->subscribers()->unverified()->getQuery();
        $unverifiedCount = $subscribersQuery->count();
        if ($unverifiedCount == 0) {
            $this->monitor->updateJsonData([
                'message' => trans('messages.list.verification.progress.done'),
                'batch_info' => null, // clean up batch information
                'percentage' => 100,
            ]);

            $logger->info('No contact left, verification done');
            return;
        }

        $perSubmit = (int) floor($unverifiedCount / static::NUMBER_OF_BATCHES);

        if ($perSubmit < static::MIN_PER_BATCH) {
            $perSubmit = static::MIN_PER_BATCH;
        } elseif ($perSubmit > static::MAX_PER_BATCH) {
            $perSubmit = static::MAX_PER_BATCH;
        }

        if ($unverifiedCount < $perSubmit) {
            $perSubmit = $unverifiedCount;
        }

        if (!$creditTracker->isUnlimited()) {
            $remainingCredits = $creditTracker->getRemainingCredits();
            if ($remainingCredits >= $perSubmit) {
                $logger->info("+ Remaining credits is {$remainingCredits}, ok to take {$perSubmit}");
            } else {
                $logger->info("+ Remaining credits is only {$remainingCredits}!");
                $perSubmit = $remainingCredits;
            }
        } else {
            $this->mailList->logger()->info("+ Good news, we have unlimited verification credits. Taking {$perSubmit} per batch");
        }

        if ($serviceCredits  < $perSubmit) {
            $perSubmit = $serviceCredits;
            $logger->info("Available service credit is only {$serviceCredits}! So, only take {$serviceCredits}!");
        } else {
            $logger->info("Available service credit is {$serviceCredits}! ok to take {$perSubmit}");
        }

        $subscribersQuery = $subscribersQuery->limit($perSubmit);

        try {
            // Submit a partition of list to the remote verification service
            // Get the session token or id, use it to check status
            $token = $this->server->getServiceClient()->bulkSubmit($subscribersQuery);
            $this->mailList->logger()->info("+ ==> Submitted! Waiting for BulkCheck job...");

            // Tricky / ugly code, count credits, no concurrency safe
            if (!$creditTracker->isUnlimited()) {
                for ($i = 0; $i < $perSubmit; $i += 1) {
                    $creditTracker->count();
                }
            }

            $this->mailList->logger()->info("+ {$perSubmit} credits counted, {$creditTracker->getRemainingCredits()} remains.");

            sleep(1);

            // Dispatch checker job
            $bulkCheck = new BulkCheck($this->mailList, $this->server, $this->subscription, $token);
            $bulkCheck->setMonitor($this->monitor);
            $this->batch()->add($bulkCheck);
        } catch (RateLimitExceeded $ex) {
            $this->mailList->logger()->warning('+ Rate limit exceeded, try again in 10 seconds. '.$ex->getMessage());
            $this->release(10);
            return;
        } catch (\Throwable $ex) {
            // Laravel batch cannot catch Throwable errors (it is handled by higher level of Laravel)
            // Just catch Throwable here and throw Exception instead so that errors can be logged to job monitor
            throw new Exception('Verification failed. '.$ex->getMessage());
        }
    }
}
