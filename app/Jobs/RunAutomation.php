<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\Cache\Repository;
use Acelle\Model\Automation2;
use Illuminate\Support\Carbon;

class RunAutomation implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $timeout = 28800;
    public $maxExceptions = 1; // This is required if retryUntil is used, otherwise, the default value is 255
    public $failOnTimeout = true;

    protected $automation;
    protected $customer;
    protected $logkey = 'running_automations';
    protected $lock = 'manage-automations';

    public function retryUntil()
    {
        return now()->addDays(7);
    }

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Automation2 $automation)
    {
        $this->automation = $automation;
        $this->customer = $automation->customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();

        with_cache_lock("run_automation_{$this->automation->uid}", function () {
            $this->automation->logger()->info("Got RunAutomation lock ".getmypid());
            $this->markAsStarted();
            $this->automation->logger()->info("Logged starting information");

            $this->automation->check();

            $this->markAsFinished();
            $this->automation->logger()->info('Logged finish information');
        }, $getLockTimeout = 2, $lockTime = 7200, $waitTimeoutCallback = function () {
            $this->automation->logger()->info('Cannot get lock, silently quit');
        });
    }

    public function markAsStarted()
    {
        with_cache_lock($this->lock, function () {
            $running = Cache::get($this->logkey, []);
            $running[$this->automation->uid] = [
                'name' => $this->automation->name,
                'started_at' => now()->toString(),
                'pid' => getmypid(),
                'server' => gethostname() ?: 'Unknown',
            ];

            Cache::put($this->logkey, $running);
        }, $getLockTimeout = 5, $lockTime = 10, $waitTimeoutCallback = function () {
            applog('automation-dispatch')->info('Cannot log automation start: '.$this->automation->uid);
        });

    }

    public function markAsFinished()
    {
        with_cache_lock($this->lock, function () {
            $running = Cache::get($this->logkey, []);
            unset($running[$this->automation->uid]);

            Cache::put($this->logkey, $running);
        }, $getLockTimeout = 5, $lockTime = 10, $waitTimeoutCallback = function () {
            applog('automation-dispatch')->info('Cannot log automation finish: '.$this->automation->uid);
        });
    }
}
