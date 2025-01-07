<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use Acelle\Library\Lockable;

class ExecuteScheduledCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $lockFile = storage_path('tmp/check-and-execute-scheduled-campaign');
        $lock = new Lockable($lockFile);
        $timeout = 5; // seconds
        $timeoutCallback = function () {
            // pass this to the getExclusiveLock method
            // to have it silently quit, without throwing an exception
            return;
        };

        $lock->getExclusiveLock(function ($f) {
            $this->withScheduledCampaigns(function ($customer, $campaigns) {
                foreach ($campaigns as $campaign) {
                    // This is called in the console, so a default db connection must be set
                    $campaign->execute($force = false, ACM_QUEUE_TYPE_BATCH);
                }
            });
        }, $timeout, $timeoutCallback);

        return 0;
    }

    private function withScheduledCampaigns($callback)
    {
        $customers = \Acelle\Model\Customer::all();
        foreach ($customers as $customer) {
            $customer->setUserDbConnection();
            $campaigns = $customer->local()->campaigns()->scheduled()->get();
            $callback($customer, $campaigns);
        }
    }
}
