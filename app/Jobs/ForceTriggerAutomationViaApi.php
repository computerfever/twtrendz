<?php

namespace Acelle\Jobs;

use Exception;

class ForceTriggerAutomationViaApi extends Base
{
    protected $automation;
    protected $customer;

    public $timeout = 7200;

    public function __construct($automation)
    {
        $this->automation = $automation;
        $this->customer = $this->automation->customer;

        if (!$this->automation->allowApiCall()) {
            throw new Exception(sprintf('Automation "%s" is not set up to be triggered via API. Cannot start!', $this->automation->name));
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();

        if (!$this->automation->allowApiCall()) {
            $this->automation->logger()->info(sprintf('Automation "%s" is not set up to be triggered via API (job handle)', $this->automation->name));
            throw new Exception("Automation is not set up to be triggered via API (job handle)");
        }
        $this->automation->logger()->info(sprintf('Actually run automation "%s" in response to API call', $this->automation->name));
        $this->automation->forceTrigger();
    }
}
