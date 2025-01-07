<?php

namespace Acelle\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class RerunCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:rerun';

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
        $this->withSendingCampaigns(function ($customer, $campaigns) {
            // Check if they are actually running!
            foreach ($campaigns as $campaign) {
                $info = $campaign->debug();

                if (is_null($info)) {
                    $campaign->logger()->warning("Audit: Campaign {$campaign->name} does not have debug info");
                    continue;
                }

                if (!array_key_exists('last_activity_at', $info)) {
                    $campaign->logger()->warning("Audit: Campaign {$campaign->name} does not have 'last_activity_at' information");
                    continue;
                }

                $lastActivityAt = Carbon::parse($info['last_activity_at']);
                $now = Carbon::now();
                $triggerIfExceeds = 300; // Auto rerun a campaign if it has been 'sending' idle for the last 300 seconds (5 minutes)
                $diffInSeconds = $now->diffInSeconds($lastActivityAt);

                if ($diffInSeconds > $triggerIfExceeds) {
                    $notice = sprintf("Audit: Campaign '%s' is pending (last updated '%s'), force resuming...", $campaign->name, $lastActivityAt->diffForHumans());
                    $campaign->logger()->warning($notice);
                    $campaign->execute($force = true, ACM_QUEUE_TYPE_BATCH);
                } else {
                    // Just fine
                    $campaign->logger()->warning(sprintf("Audit: campaign '%s' was last updated '%s', it is just fine", $campaign->name, $lastActivityAt->diffForHumans()));
                }
            }
        });

        return 0;
    }

    private function withSendingCampaigns($callback)
    {
        $customers = \Acelle\Model\Customer::all();
        foreach ($customers as $customer) {
            $customer->setUserDbConnection();
            $campaigns = $customer->local()->campaigns()->sending()->get();
            $callback($customer, $campaigns);
        }
    }
}
