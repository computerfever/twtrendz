<?php

namespace Acelle\Jobs;

use Acelle\Model\AutoTrigger;
use Acelle\Model\Subscriber;
use Acelle\Model\SendingServer;
use Acelle\Model\Subscription;
use Acelle\Library\Exception\RateLimitExceeded;
use Acelle\Library\RouletteWheel;
use Throwable;

class SendSingleMessage extends SendMessage
{
    protected $autoTriggerId;
    protected $autoTrigger;
    protected $actionId;
    protected $action;

    public function __construct($campaign, Subscriber $subscriber, RouletteWheel $servers, Subscription $subscription = null, $autoTriggerId = null, $actionId = null)
    {
        parent::__construct($campaign, $subscriber, $servers, $subscription);

        $this->autoTriggerId = $autoTriggerId;
        $this->actionId = $actionId;
    }

    public function afterSuccess()
    {
        $this->campaign->logger()->info('Updating trigger status (sent_at)');

        $this->getAutoTrigger()->withUpdateLock(function ($trigger) {
            // Mark the related trigger action as done

            $action = $this->getAction();
            $action->setSent();
            $trigger->check(); // no need to pass $manually = true here, as this action is only to confirm the completion of Send (execute returns true, then last_executed_time is recorded, done)
            $this->campaign->logger()->info('Updated trigger status (sent_at)');
        });
    }

    public function getAutoTrigger()
    {
        if (is_null($this->autoTrigger)) {
            if ($this->customer->hasLocalDb()) {
                $connection = $this->customer->db_connection;
            } else {
                $connection = 'mysql';
            }

            $this->autoTrigger = AutoTrigger::on($connection)->find($this->autoTriggerId);
        }

        if (is_null($this->autoTrigger)) {
            throw new \Exception("Cannot find trigger {$this->autoTriggerId} (DB connection ".\DB::connection()->getName().")");
        }

        return $this->autoTrigger;
    }

    public function getAction()
    {
        if (is_null($this->action)) {
            $this->action = $this->getAutoTrigger()->getActionById($this->actionId);
        }

        return $this->action;
    }

    public function handle()
    {
        // Set default connection
        $this->customer->setUserDbConnection();
        $this->send();
    }

    public function handleRateLimitExceeded($email, RateLimitExceeded $ex)
    {
        $secondsToDelay = 600;
        $this->campaign->logger()->warning(sprintf("Delay [%s] for %s seconds (no batch): %s", $email, $secondsToDelay, $ex->getMessage()));

        $this->getAutoTrigger()->withUpdateLock(function ($trigger) use ($email, $ex, $secondsToDelay) {
            $delayNote = sprintf("Delayed for %s seconds: %s", $secondsToDelay, $ex->getMessage());

            $action = $this->getAction();
            $action->setOption('delay_note', $delayNote);
            $action->save();
        });

        $this->release($secondsToDelay); // should be only 60 seconds
        return $secondsToDelay;
    }

    public function handleUnknownException($ex)
    {
        $this->getAutoTrigger()->withUpdateLock(function ($trigger) use ($ex) {
            // Mark the related trigger action as done
            $action = $this->getAction();
            $action->setError($ex->getMessage());
            $this->campaign->logger()->info('Error sending trigger email: '.$ex->getMessage());
        });

        // Return true is important, telling the caller not to throw exception
        return true;
    }
}
