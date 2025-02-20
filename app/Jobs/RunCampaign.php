<?php

namespace Acelle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Acelle\Library\Traits\Trackable;
use Acelle\Library\Contracts\CampaignInterface;
use Carbon\Carbon;
use DB;

class RunCampaign implements ShouldQueue
{
    use Trackable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected CampaignInterface $campaign;
    protected string $campaignQueue;  // Do not use the $queue name, already reserved by Illuminate\Bus\Queueable
    protected $customer;

    public $timeout = 300;
    public $failOnTimeout = true;
    public $tries = 1;
    public $maxExceptions = 1;


    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CampaignInterface $campaign, string $queue)
    {
        $this->campaign = $campaign;
        $this->campaignQueue = $queue;

        // For the sake of DB sharding
        // We need a customer in the master db which helps
        // establishing a correct connection
        $this->customer = $this->campaign->customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Do not use customer->setDbConnection() here, it might mess up with batch!!!!!!!!
        if ($this->customer->hasLocalDb()) {
            $this->campaign->logger()->info("Loading campaign object on connection {$this->customer->db_connection}");
            $this->campaign = \Acelle\Model\Campaign::on($this->customer->db_connection)->find($this->campaign->id);
        }

        if ($this->campaign->isPaused()) {
            return;
        }

        try {
            $sessionId = date('Y-m-d_H:i:s');
            $startAt = Carbon::now();
            $this->campaign->cleanupDebug();
            $this->campaign->debug(function ($info) use ($startAt) {
                $info['start_at'] = $startAt->toString();
                return $info;
            });

            $this->campaign->logger()->info("Launch campaign from job: session {$sessionId}");

            $this->campaign->logger()->warning('Set up campaign before sending');
            $this->campaign->prepare();
            $this->campaign->logger()->warning('Done setting up campaign before sending');
            $this->campaign->run($check = true, $this->campaignQueue);

            // after executing campaign.run(), this job just finishes
        } catch (\Throwable $e) {
            $errorMsg = "Error scheduling campaign: ".$e->getMessage()."\n".$e->getTraceAsString();

            // In case the error message size is too large
            $errorMsg = substr($errorMsg ?? '', 0, 1000);

            $this->campaign->setError($errorMsg);

            // To set the job to failed
            throw $e;
        }
    }
}
