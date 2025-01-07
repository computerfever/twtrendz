<?php

namespace Acelle\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Exception;
use Acelle\Events\MailListSubscription;
use Acelle\Events\MailListUnsubscription;
use Acelle\Events\SubscribersTagsUpdate;
use Acelle\Events\SubscribersAttributesUpdate;
use Acelle\Events\SubscriberTagsRemoved;
use Acelle\Model\Field;
use Acelle\Model\Automation2;

class TriggerAutomation
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  MailListSubscription  $event
     * @return void
     */
    public function handleMailListSubscription(MailListSubscription $event)
    {
        $automations = $event->subscriber->mailList->automations;
        $automations = $automations->filter(function ($auto, $key) {
            return $auto->isActive() && (
                $auto->getTriggerType() == Automation2::TRIGGER_TYPE_WELCOME_NEW_SUBSCRIBER
            );
        });

        foreach ($automations as $auto) {
            if (is_null($auto->getAutoTriggerFor($event->subscriber))) {
                $segments = $auto->getSegments();

                // If there is no segment condition, trigger the contact
                if ($segments->isEmpty()) {
                    $trigger = $auto->initTrigger($event->subscriber);
                    $trigger->check($manually = true); // manually here means "by individual users"

                    continue;
                }

                $matched = false;
                foreach ($segments as $segment) {
                    if ($segment->isSubscriberIncluded($event->subscriber)) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    $trigger = $auto->initTrigger($event->subscriber);
                    $trigger->check($manually = true);
                }
            }
        }
    }

    /**
     * Handle the event.
     *
     * @param  MailListSubscription  $event
     * @return void
     */
    public function handleMailListUnsubscription(MailListUnsubscription $event)
    {
        $automations = $event->subscriber->mailList->automations;
        $automations = $automations->filter(function ($auto, $key) {
            return $auto->isActive() && (
                $auto->getTriggerType() == Automation2::TRIGGER_TYPE_SAY_GOODBYE_TO_SUBSCRIBER
            );
        });

        foreach ($automations as $auto) {
            if (is_null($auto->getAutoTriggerFor($event->subscriber))) {
                $forceTriggerUnsubscribedContact = true;
                $trigger = $auto->initTrigger($event->subscriber, $forceTriggerUnsubscribedContact);
                $trigger->check($manually = true);
            }
        }
    }

    public function handleSubscribersTagsUpdate(SubscribersTagsUpdate $event)
    {
        $automations = $event->subscriber->mailList->automations;
        $automations = $automations->filter(function ($auto, $key) {
            return $auto->isActive() && (
                $auto->getTriggerType() == Automation2::TRIGGER_TAG_BASED
            );
        });

        foreach ($automations as $auto) {
            $tags = $auto->getTriggerAction()->getOption('tags');
            if ($event->subscriber->hasTags($tags)) {
                $existingTrigger = $auto->getAutoTriggerFor($event->subscriber);

                if (is_null($existingTrigger)) {
                    $trigger = $auto->initTrigger($event->subscriber);
                    $trigger->check($manually = true);
                } else {
                    $auto->logger()->info('Tag matched but already triggered');
                }
            }
        }
    }

    public function handleSubscribersAttributesUpdate(SubscribersAttributesUpdate $event)
    {

        $automations = $event->subscriber->mailList->automations;
        $automations = $automations->filter(function ($auto, $key) {
            return $auto->isActive() && (
                $auto->getTriggerType() == Automation2::TRIGGER_ATTRIBUTE_UPDATE
            );
        });

        foreach ($automations as $auto) {
            $fieldUid = $auto->getTriggerAction()->getOption('field_uid');
            $field = Field::findByUid($fieldUid);

            if (is_null($field)) {
                throw new Exception("Field UID does not exist", 1);
            }

            $isFieldChanged = in_array($field->uid, array_keys($event->changes));

            if (!$isFieldChanged) {
                $auto->logger()->info("Automation '{$auto->name}' is set up to trigger on {$field->tag} change of {$event->subscriber->email}. However, this field's value does not change");
                continue;
            } else {
                $auto->logger()->info("Automation '{$auto->name}' is set up to trigger on {$field->tag} change of {$event->subscriber->email}");
            }

            $fieldNewValue = $event->subscriber->getValueByField($field);
            $requiredValue = $auto->getTriggerAction()->getOption('value');

            if ($isFieldChanged && $fieldNewValue == $requiredValue) {
                $existingTrigger = $auto->getAutoTriggerFor($event->subscriber);

                if (is_null($existingTrigger)) {
                    $auto->logger()->info("Field {$field->tag} changes to '{$fieldNewValue}', okay, triggering...");
                    $trigger = $auto->initTrigger($event->subscriber);
                    $trigger->check($manually = true);
                } else {
                    $auto->logger()->info("Field {$field->tag} changes to '{$fieldNewValue}', HOWEVER, an auto trigger already exists");
                }
            } else {
                $auto->logger()->info("Condition to trigger automation '{$auto->name}': field {$field->tag} = '{$requiredValue}', however, the new value of {$event->subscriber->email} it is actually {$field->tag} = '{$fieldNewValue}'");
            }
        }
    }

    public function handleSubscriberTagsRemoved(SubscriberTagsRemoved $event)
    {
        // Now check for tag
        $automations = $event->subscriber->mailList->automations;
        $automations = $automations->filter(function ($auto, $key) {
            return $auto->isActive() && (
                $auto->getTriggerType() == Automation2::TRIGGER_REMOVE_TAG
            );
        });

        foreach ($automations as $auto) {
            $tagsToCheck = $auto->getTriggerAction()->getOption('tags');

            foreach ($event->removedTags as $tag) {
                if (!in_array($tag, $tagsToCheck)) {
                    $auto->logger()->info("Tag '{$tag}' was removed, but it is not monitored by automation {$auto->name} (".implode(",", $tagsToCheck)."), checking next tag");
                    continue;
                }

                // Ok, at least one removed tag is monitored
                $existingTrigger = $auto->getAutoTriggerFor($event->subscriber);
                if (is_null($existingTrigger)) {
                    $auto->logger()->info("Hey, tag '{$tag}' was recently dropped for this contact {$event->subscriber->email}, triggering");
                    $trigger = $auto->initTrigger($event->subscriber);
                    $trigger->check($manually = true);
                } else {
                    $auto->logger()->info("Tag '{$tag}' was recently dropped for this contact {$event->subscriber->email}, but a trigger already exists");
                }

                $auto->logger()->info("OK, tags were removed (".implode(",", $event->removedTags).") automation was triggered");

                // no need to check the remaining tags of the same automation
                break;
            }
        }
    }

    // Subscribe to many events
    public function subscribe($events)
    {
        $events->listen(
            'Acelle\Events\MailListSubscription',
            [TriggerAutomation::class, 'handleMailListSubscription']
        );

        $events->listen(
            'Acelle\Events\MailListUnsubscription',
            [TriggerAutomation::class, 'handleMailListUnsubscription']
        );

        $events->listen(
            SubscribersTagsUpdate::class,
            [TriggerAutomation::class, 'handleSubscribersTagsUpdate']
        );

        $events->listen(
            SubscribersAttributesUpdate::class,
            [TriggerAutomation::class, 'handleSubscribersAttributesUpdate']
        );

        $events->listen(
            SubscriberTagsRemoved::class,
            [TriggerAutomation::class, 'handleSubscriberTagsRemoved']
        );
    }
}
