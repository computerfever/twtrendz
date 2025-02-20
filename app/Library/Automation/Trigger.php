<?php

namespace Acelle\Library\Automation;

class Trigger extends Action
{
    protected function doExecute($manually)
    {
        return true;
    }

    public function getActionDescription()
    {
        $nameOrEmail = $this->autoTrigger->getSubscriberCachedInfo('email', $fallback = true, $default = '[email]');

        return sprintf('User %s subscribes to mail list, automation triggered!', $nameOrEmail);
    }

    public function getProgressDescription($timezone = null, $locale = null)
    {
        $lastExecuted = $this->getLastExecuted();
        $createdAt = $this->autoTrigger->created_at->timezone($timezone);

        if (is_null($lastExecuted)) {
            return trans('messages.automation.trigger.trigger.progress.created', [
                'dif' => $createdAt->diffForHumans(),
                'dt' => format_datetime($createdAt, 'datetime_full_with_timezone', $locale)
            ]);
        } else {
            return trans('messages.automation.trigger.trigger.progress.done', [
                'dif' => $createdAt->diffForHumans(),
                'dt' => format_datetime($createdAt, 'datetime_full_with_timezone', $locale)
            ]);
        }
    }

    public function isDelayAction()
    {
        return true;
    }
}
