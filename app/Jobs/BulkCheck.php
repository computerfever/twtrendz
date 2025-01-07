<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Acelle\Library\Traits\Trackable;
use Acelle\Model\Subscription;
use Acelle\Model\MailList;
use Acelle\Model\EmailVerificationServer;
use DB;
use Exception;

use function Acelle\Helpers\create_temp_db_table;

class BulkCheck implements ShouldQueue
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

    protected $server;
    protected $mailList;
    protected $subscription;
    protected $token;
    protected $customer;

    public function __construct(MailList $mailList, EmailVerificationServer $server, Subscription $subscription, $token)
    {
        $this->mailList = $mailList;
        $this->server = $server;
        $this->subscription = $subscription;
        $this->token = $token;

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
            $this->mailList->logger()->info('- Cancelled, token: '.$this->token);
            return;
        }

        $this->mailList->logger()->info('- Checking with token: '.$this->token);

        $results = [];
        $serviceClient = $this->server->getServiceClient();

        try {
            $check = $serviceClient->bulkCheck($this->token, function ($email, $verificationStatus, $raw) use (&$results) {
                $results[] = [
                    'email' => $email,
                    'status' => $verificationStatus,
                    'raw' => $raw,
                ];
            }, function (string $response, $batchId = null, $batchSize = null, $processed = null) {

                if ($batchId && $batchSize) {
                    $this->monitor->updateJsonData([
                        'batch_info' => trans('messages.list.verification.progress.batch_info', [
                            'j' => $batchId,
                            'p' => $processed,
                            't' => $batchSize,
                        ])
                    ]);
                }

                $this->mailList->logger()->info('- Wait: '.$response);
            });
        } catch (\ZeroBounce\SDK\ZBException $ex) {
            // In case of stupid error by ZB
            $this->mailList->logger()->info('- \ZeroBounce\\SDK\\ZBException from ZB, release(10) and wait. Error: '.$ex->getMessage());
            $this->release(10);
            return;
        } catch (\Throwable $ex) {
            $this->mailList->logger()->warning('Failed (catched by Throwable), throw exception. Error: '.$ex->getMessage());
            throw new Exception('Verification CHECK failed. '.$ex->getMessage());
        }


        if ($check) {
            $this->updateVerificationResults($results);

            $count = $this->monitor->getJsonData()['count'] ?? 0;
            $count += sizeof($results);
            $percentageDone = round($this->mailList->getVerifiedSubscribersPercentage() * 100, 2);

            $msg = trans('messages.list.verification.progress.running', [
                'c' => $count,
                'v' => $this->mailList->subscribers()->verified()->count(),
                't' => $this->mailList->subscribers()->count(),
            ]);

            $this->monitor->updateJsonData([
                'message' => $msg,
                'percentage' => $percentageDone,
                'count' => $count,
            ]);

            $this->mailList->logger()->info('- List % verified: '.$percentageDone);

            // Check done, launch a new round
            $job = new BulkVerify($this->mailList, $this->server, $this->subscription);
            $job->setMonitor($this->monitor);
            $this->batch()->add($job);
            return;
        } else {
            $this->mailList->logger()->info('- Check again in 10 seconds');

            // wait until result is available, check again
            $this->release(10);
            return;
        }
    }

    private function updateVerificationResults($results)
    {
        $tmpFields = [
            "`email` TEXT",
            "`status` TEXT",
            "`raw` TEXT",
        ];

        if (empty($results)) {
            $this->mailList->logger()->warning("- Empty verification results");
        } else {
            $sample = implode(', ', array_map(
                function ($r) {
                    return $r['email'];
                },
                array_slice($results, 0, 4)
            ));
            $this->mailList->logger()->info("- ==> Updating verification results (".sizeof($results)." records): {$sample}...");
        }

        create_temp_db_table($tmpFields, $results, function ($tmpTable) {
            $nameWithPrefix = table($tmpTable);

            // IMPORTANT: do not use printf(), use sprintf() instead. printf produces and additional trailing enter char that causes invalid SQL syntax
            $sql = sprintf("
                UPDATE %s as s INNER JOIN %s t ON s.email = t.email
                SET
                  s.verification_status = t.status,
                  s.last_verification_at = NOW(),
                  s.last_verification_by = %s,
                  s.last_verification_result = t.raw
                WHERE mail_list_id = %s
            ", table('subscribers'), $nameWithPrefix, db_quote($this->server->name), $this->mailList->id);

            DB::statement($sql);
        });
    }
}
