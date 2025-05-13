<?php

namespace Acelle\Library\HtmlHandler;

use League\Pipeline\StageInterface;
use Acelle\Library\StringHelper;

class TransformTag implements StageInterface
{
    public $campaign;
    public $subscriber;
    public $msgId;
    public $server;

    // Campaign or email
    public function __construct($campaign, $subscriber, $msgId, $server = null)
    {
        $this->campaign = $campaign;
        $this->subscriber = $subscriber;
        $this->msgId = $msgId;
        $this->server = $server;
    }
    public function __invoke($html)
    {
        // DEPRECATED
        if (!is_null($this->server) && $this->server->isElasticEmailServer()) {
            $html = $this->server->addUnsubscribeUrl($html);
        }

        $tags = array(
            'CAMPAIGN_NAME' => $this->campaign->name,
            'CAMPAIGN_UID' => $this->campaign->uid,
            'CAMPAIGN_SUBJECT' => $this->campaign->subject,
            'CAMPAIGN_FROM_EMAIL' => $this->campaign->from_email,
            'CAMPAIGN_FROM_NAME' => $this->campaign->from_name,
            'CAMPAIGN_REPLY_TO' => $this->campaign->reply_to,
            'CURRENT_YEAR' => date('Y'),
            'CURRENT_MONTH' => date('m'),
            'CURRENT_DAY' => date('d')
        );

        // Use in case $subscriber or $msgId is null
        $sampleLink = $this->campaign->makeSampleLink();
        
        $fallbackTags = json_decode($this->campaign->tagsFallbackValues,true);

        # Subscriber specific
        if (is_null($this->subscriber) || $this->campaign->isStdClassSubscriber($this->subscriber)) {
            $tags['UNSUBSCRIBE_URL'] = $sampleLink;
            $tags['UPDATE_PROFILE_URL'] = $sampleLink;
            $tags['WEB_VIEW_URL'] = $sampleLink;
            $tags['SUBSCRIBER_UID'] = '%UID%';

            $tags['LIST_UID'] = '%LIST-UID%';
            $tags['LIST_NAME'] = '%LIST-NAME%';
            $tags['LIST_FROM_NAME'] = '%LIST-FROM-NAME%';
            $tags['LIST_FROM_EMAIL'] = '%LIST-FROM-EMAIL%';
            $tags['CONSULTANT_ID'] = '%CONSULTANT_ID%';
            $tags['CONSULTANT_MSG'] = '%CONSULTANT_MSG%';
            $tags['first_name'] = '%FIRST_NAME%';
            $tags['last_name'] = '%LAST_NAME%';
            $tags['PHONE'] = '%PHONE%';
            $tags['email'] = '%EMAIL%';
            $tags['URL'] = '%URL%';
            $tags['image'] = '%IMAGE%';
            $tags['profile_photo'] = '%PROFILE_PHOTO%';

            // Subscriber custom fields, including email
            $sample = '%PERSONALIZED-DATA%';

            // all lists assocated with this campaign/email
            // Notice that the Email model doesn ot have mailLists association, only defaultMailList

            if (!$this->campaign->mailLists) {
                foreach ($this->campaign->defaultMailList->fields as $field) {
                    $tags['SUBSCRIBER_'.$field->tag] = $sample;
                    $tags[$field->tag] = $sample;
                }
            } else {
                foreach ($this->campaign->mailLists as $list) {
                    foreach ($list->fields as $field) {
                        $tags['SUBSCRIBER_'.$field->tag] = $sample;
                        $tags[$field->tag] = $sample;
                    }
                }
            }

            // Special / shortcut fields
            $tags['NAME'] = $sample;
            $tags['FULL_NAME'] = $sample;

            // Only email is "reserved", overwrite previous $sample
            $tags['SUBSCRIBER_EMAIL'] = is_null($this->subscriber) ? 'email@sample.com' : $this->subscriber->email;
        } else {
            $tags['LIST_UID'] = @$this->subscriber->mailList->uid;
            $tags['LIST_NAME'] = @$this->subscriber->mailList->name;
            $tags['LIST_FROM_NAME'] = @$this->subscriber->mailList->from_name;
            $tags['LIST_FROM_EMAIL'] = @$this->subscriber->mailList->from_email;
            // $tags['CONSULTANT_ID'] = print_r($this->subscriber->mailList->customer->contact);

            $tags['CONSULTANT_ID'] = @$this->subscriber->mailList->customer->contact->consultant_id;
            $tags['CONSULTANT_MSG'] = @$this->subscriber->mailList->customer->contact->message;
            $tags['first_name'] = @$this->subscriber->mailList->customer->contact->first_name;
            $tags['last_name'] = @$this->subscriber->mailList->customer->contact->last_name;
            $tags['PHONE'] = @$this->subscriber->mailList->customer->contact->phone;
            $tags['email'] = @$this->subscriber->mailList->customer->contact->email;
            $tags['URL'] = @$this->subscriber->mailList->customer->contact->url;
            $tags['image'] = @$this->subscriber->mailList->customer->contact->image;
            $tags['profile_photo'] = @$this->subscriber->mailList->customer->user->getProfileImageUrl();

            if(@$this->subscriber->mailList->customer->contact->country->name == "Canada"){
                $html = str_replace('https://www.tupperware.com/','https://www.tupperware.ca/', $html);
                $html = str_replace('twcId=US','twcId=CA', $html);
            }

            $updateProfileUrl = $this->subscriber->generateUpdateProfileUrl();

            if (is_null($this->msgId)) {
                $unsubscribeUrl = $sampleLink;
                $webViewUrl = $sampleLink;
            } else {
                $unsubscribeUrl = $this->subscriber->generateUnsubscribeUrl($this->msgId);
                $webViewUrl = StringHelper::generateWebViewerUrl($this->msgId);
            }

            if ($this->campaign->trackingDomain) {
                $updateProfileUrl = $this->campaign->trackingDomain->buildTrackingUrl($updateProfileUrl);
                $unsubscribeUrl = $this->campaign->trackingDomain->buildTrackingUrl($unsubscribeUrl);
                $webViewUrl = $this->campaign->trackingDomain->buildTrackingUrl($webViewUrl);
            }

            $tags['UPDATE_PROFILE_URL'] = $updateProfileUrl;
            $tags['UNSUBSCRIBE_URL'] = $unsubscribeUrl;
            $tags['WEB_VIEW_URL'] = $webViewUrl;
            $tags['SUBSCRIBER_UID'] = $this->subscriber->uid;

            # Subscriber custom fields
            foreach ($this->subscriber->mailList->fields as $field) {
                $tags['SUBSCRIBER_'.$field->tag] = $this->subscriber->getValueByField($field);
                // $tags[$field->tag] = $this->subscriber->getValueByField($field);
            }

            // Special / shortcut fields
            $tags['NAME'] = $this->subscriber->getFullName();
            $tags['FULL_NAME'] = $this->subscriber->getFullName();
        }

        foreach ($tags as $key => $value) {
            if(empty($tags[$key]) AND !empty($fallbackTags)){
                if(array_key_exists($key,$fallbackTags)){
                    $tags[$key] = $fallbackTags[$key];
                }
            }
        }

        // Actually transform the message
        foreach ($tags as $tag => $value) {
            $html = str_replace('{'.$tag.'}', $value ?? '#', $html);
        }

        return $html;
    }
}