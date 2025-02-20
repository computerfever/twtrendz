<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Repository;
use Acelle\Model\Customer;

class DispatchAutomationJobs implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 1200;
    public $maxExceptions = 1; // This is required if retryUntil is used, otherwise, the default value is 255
    public $failOnTimeout = true;

    /**
     * Determine the time at which the job should timeout.
     *
     * @return \DateTime
     */
    public function retryUntil()
    {
        return now()->addDays(7);
    }

    public function handle()
    {
        with_cache_lock('lock_run_all_automations', function () {
            applog('automation-dispatch')->info('Got lock, running...');
            $this->run();
        }, $getLockTimeout = 5, $lockExpiresAfter = 1200, $timeoutCallback = function () {
            applog('automation-dispatch')->info('Abort: cannot get lock after 5 seconds');
        });
    }

    private function run()
    {
        applog('automation-dispatch')->info('Start dispatching automation jobs');
        $customers = Customer::all();
        foreach ($customers as $customer) {
            if (config('app.saas') && is_null($customer->getCurrentActiveGeneralSubscription())) {
                continue;
            }

            $automations = $customer->local()->activeAutomation2s;

            foreach ($automations as $automation) {
                $job = new RunAutomation($automation);
                $jobId = safe_dispatch($job->onQueue(ACM_QUEUE_TYPE_AUTOMATION));

                $msg = sprintf('Dispatch automation "%s", Job #%s', $automation->name, $jobId);
                $automation->logger()->info($msg);
                applog('automation-dispatch')->info('- '.$msg);
            }
        }
        applog('automation-dispatch')->info('Finish!');
    }
}
