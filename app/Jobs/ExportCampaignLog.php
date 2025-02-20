<?php

namespace Acelle\Jobs;

use Acelle\Library\Traits\Trackable;

class ExportCampaignLog extends Base
{
    use Trackable;

    public $timeout = 3600;

    protected $campaign;
    protected $logtype;
    protected $customer;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($campaign, $logtype)
    {
        $this->campaign = $campaign;
        $this->logtype = $logtype;
        $this->customer = $this->campaign->customer;

        // Set the initial value for progress check
        $this->afterDispatched(function ($thisJob, $monitor) {
            $monitor->setJsonData([
                'percentage' => 0
            ]);
        });
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();
        $this->campaign->generateTrackingLogCsv($this->logtype, function ($percentage, $path) {
            $this->monitor->updateJsonData([
                'percentage' => $percentage,
                'path' => $path,
            ]);
        });
    }
}
