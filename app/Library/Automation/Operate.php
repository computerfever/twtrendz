<?php

namespace Acelle\Library\Automation;

use Acelle\Model\MailList;
use Exception;

class Operate extends Action
{
    public const OPERATION_TAG = 'tag';
    public const OPERATION_COPY = 'copy';
    public const OPERATION_MOVE = 'move';
    public const OPERATION_UPDATE = 'update';
    public const OPERATION_REMOVE_TAG = 'remove_tag';

    /*****
        Operate action may result in the following cases:
          + Done OK
          + Exception (any type of exception...)
        In case of Exception, it is better to stop the whole automation process and write error log to the automation
        so that the responsible person can check it

        Then, "last_executed" is used as a flag indicating that the process is done
        Execution always returns TRUE

    ****/
    protected function doExecute($manually)
    {
        if (config('app.demo') == 'true') {
            return true;
        }

        $operationType = $this->getOption('operation_type');
        $subscriber = $this->autoTrigger->subscriber;

        if ($operationType == self::OPERATION_TAG) {
            $tags = $this->getOption('tags');
            $subscriber->updateTags($tags, $merge = true);
            $this->logger->info(sprintf('* Tag contact "%s" with: %s', $subscriber->email, implode(', ', $tags)));
        } elseif ($operationType == self::OPERATION_COPY || $operationType == self::OPERATION_MOVE) {
            $toListUid = $this->getOption('target_list_uid');

            if (is_null($toListUid)) {
                throw new Exception("Cannot copy/move contact, target list not set");
            }

            $toList = MailList::findByUid($toListUid);

            if (is_null($toList)) {
                throw new Exception("Cannot copy/move contact, target list does not exist: {$toListUid}");
            }

            $duplicateCallback = function ($subscriber_) use ($operationType, $toList) {
                $this->logger->info(sprintf('Notice: skip %s contact "%s" to list "%s". Duplicate email', $operationType, $subscriber_->email, $toList->name));
            };

            if ($operationType == self::OPERATION_MOVE) {
                // Temporarily put cacheSubscriberInfo() here, for backward compatibility
                // In the future, there should be only 1 place where it is called
                $this->autoTrigger->cacheSubscriberInfo();

                // After move action, the subscriber_id of Autotrigger shall become null
                $subscriber->move($toList, $duplicateCallback);
            } else {
                $subscriber->copy($toList, $duplicateCallback);
            }

            $this->logger->info(sprintf('DONE: %s contact "%s" to list "%s"', $operationType, $this->autoTrigger->getSubscriberCachedInfo('email', $fallback = true, $default = '[email]'), $toList->name));
        } elseif ($operationType == self::OPERATION_UPDATE) {
            $updates = $this->getOption('update');
            // $updates is a PHP array equiv. to:
            // [{"field_uid":"668a855841b1d","value":"Field Value"},{"field_uid":"668a855843d3b","value":"Field Value"}]
            $subscriber->bulkUpdate($updates);
        } elseif (self::OPERATION_REMOVE_TAG == $operationType) {
            $tags = $this->getOption('tags');
            $subscriber->removeTags($tags);
        } else {
            throw new Exception("Unknown operation type: {$operationType}");
        }

        // Return true
        return true;
    }

    // Overwrite
    public function getActionDescription()
    {
        return sprintf('Perform an operation');
    }

    public function getProgressDescription($timezone = null, $locale = null)
    {
        $subscriberEmail = $this->autoTrigger->getSubscriberCachedInfo('email', $fallback = true, $default = '[email]');
        $operationType = $this->getOption('operation_type');

        if ($operationType == self::OPERATION_TAG) {
            $tags = $this->getOption('tags');

            return sprintf('* Tag contact "%s" with: %s', $subscriberEmail, implode(', ', $tags));
        } elseif ($operationType == self::OPERATION_COPY) {
            $toListUid = $this->getOption('target_list_uid');

            if (is_null($toListUid)) {
                throw new Exception("Cannot get trigger info contact, target list not set");
            }

            $toList = MailList::findByUid($toListUid);

            if (is_null($toList)) {
                throw new Exception("Cannot get trigger information, list does not exist: {$toListUid}");
            }

            return sprintf('* Action: %s contact "%s" to list "%s"', $operationType, $subscriberEmail, $toList->name);
        } elseif ($operationType == self::OPERATION_UPDATE) {
            return sprintf('* Action: Update contact value "%s"', $subscriberEmail);
        } elseif ($operationType == self::OPERATION_MOVE) {
            $toListUid = $this->getOption('target_list_uid');
            $toList = MailList::findByUid($toListUid);

            if (is_null($toList)) {
                $toListName = 'N/A';
            } else {
                $toListName = $toList->name;
            }

            if ($this->getLastExecuted()) {
                return trans('messages.automation.action.operation.move_contact.done', ['n' => $toListName]);
            } else {
                return trans('messages.automation.action.operation.move_contact.new', ['n' => $toListName]);
            }
        } elseif ($operationType == self::OPERATION_REMOVE_TAG) {
            $tags = $tags = $this->getOption('tags');
            $tagsStr = implode(", ", $tags);
            if ($this->getLastExecuted()) {
                return trans('messages.automation.action.operation.remove_tag.done', ['t' => $tagsStr]);
            } else {
                return trans('messages.automation.action.operation.remove_tag.new', ['t' => $tagsStr]);
            }
        } else {
            throw new Exception("Unknown operation type: {$operationType}");
        }
    }
}
