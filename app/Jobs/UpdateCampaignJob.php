<?php

namespace Acelle\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Cache;

class UpdateCampaignJob extends Base implements ShouldBeUnique
{
    protected $campaign;
    protected $customer;

    public function __construct($campaign)
    {
        $this->campaign = $campaign;
        $this->customer = $this->campaign->customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();
        $this->campaign->updateCache();
    }

    public $uniqueFor = 1200; // 20 minutes
    public function uniqueId()
    {
        return $this->campaign->id;
    }

    public function uniqueVia()
    {
        return Cache::driver('file');
    }
}
