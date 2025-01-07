<?php

namespace Acelle\Jobs;

use Exception;

class RetryAllFailedActions extends Base
{
    protected $automation;
    protected $customer;

    public $timeout = 7200;

    public function __construct($automation)
    {
        $this->automation = $automation;
        $this->customer = $this->automation->customer;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->customer->setUserDbConnection();

        $failedActionsQuery = $this->automation->autoTriggers()->error();
        $this->automation->logger()->info("Retrying {$failedActionsQuery->count()} failed actions");
        foreach ($failedActionsQuery->get() as $trigger) {
            $action = $trigger->findLastActionToExecute();

            if ($action->getOption('error')) {
                $action->retry();
            } else {
                throw new Exception('Cannot retry '.$action->getId().'. Action does not have any error associated');
            }
        }
        $this->automation->logger()->info("Done retrying failed actions");
    }
}
