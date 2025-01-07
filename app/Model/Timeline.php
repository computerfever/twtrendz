<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Model;
use Acelle\Library\Traits\HasUid;

class Timeline extends Model
{
    use HasUid;

    protected $fillable = ['automation2_id', 'subscriber_id', 'auto_trigger_id', 'activity', 'activity_type'];

    public const TYPE_ADDED_BY_CUSTOMER = 'added_by_customer';
    public const TYPE_SIGN_UP_FORM_OPT_IN = 'sign_up_form_opt_in';
    public const TYPE_EMBEDDED_FORM_OPT_IN = 'embedded_form_opt_in';
    public const TYPE_POPUP_FORM_OPT_IN = 'popup_form_opt_in';
    public const TYPE_SUBSCRIBED_BY_CUSTOMER = 'subscribed_by_customer';
    public const TYPE_UNSUBSCRIBED_BY_CUSTOMER = 'unsubscribed_by_customer';
    public const TYPE_API_SUBSCRIBED = 'api_subscribed';
    public const TYPE_API_ADDED = 'api_added';
    public const TYPE_COPIED_FROM = 'copied_from';
    public const TYPE_COPIED_TO = 'copied_to';
    public const TYPE_MOVED_FROM = 'moved_from';
    public const TYPE_UNSUBSCRIBED_FROM_LIST_UNSUBSCRIBE_FORM = 'unsubscribed_from_list_unsubscribe_form';
    public const TYPE_OPENED_CAMPAIGN_EMAIL = 'opened_campaign_email';
    public const TYPE_CLICKED_CAMPAIGN_EMAIL = 'clicked_campaign_email';
    // public const TYPE_OPEN_AUTOMATION_EMAIL = 'opened_campaign_email';
    // public const TYPE_CLICK_AUTOMATION_EMAIL = 'opened_campaign_email';

    // 4 - subscriber imported (để tao xử)
    // 5 - subscriber opens a campaign email
    // 6 - subscriber clicks a campaign link

    public static function allTypes()
    {
        return [
            static::TYPE_ADDED_BY_CUSTOMER,
            static::TYPE_SIGN_UP_FORM_OPT_IN,
            static::TYPE_EMBEDDED_FORM_OPT_IN,
            static::TYPE_POPUP_FORM_OPT_IN,
            static::TYPE_SUBSCRIBED_BY_CUSTOMER,
            static::TYPE_UNSUBSCRIBED_BY_CUSTOMER,
            static::TYPE_API_SUBSCRIBED,
            static::TYPE_API_ADDED,
            static::TYPE_COPIED_FROM,
            static::TYPE_COPIED_TO,
            static::TYPE_MOVED_FROM,
            static::TYPE_UNSUBSCRIBED_FROM_LIST_UNSUBSCRIBE_FORM,
        ];
    }

    /**
     * Associations.
     *
     * @var object | collect
     */
    public function subscriber()
    {
        return $this->belongsTo('Acelle\Model\Subscriber');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function mailList()
    {
        return $this->belongsTo(MailList::class);
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public static function newDefault($subscriber, $type)
    {
        $timeline = new static();

        $timeline->subscriber_id = $subscriber->id;
        $timeline->type = $type;

        return $timeline;
    }

    // TYPE_ADDED_BY_CUSTOMER
    public static function recordAddedByCustomer($subscriber, $customer)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_ADDED_BY_CUSTOMER);

        $timeline->customer_id = $customer->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_SIGN_UP_FORM_OPT_IN
    public static function recordSignUpFormOptIn($subscriber)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_SIGN_UP_FORM_OPT_IN);

        $timeline->save();

        return $timeline;
    }

    // TYPE_EMBEDDED_FORM_OPT_IN
    public static function recordEmbeddedFormOptIn($subscriber)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_EMBEDDED_FORM_OPT_IN);

        $timeline->save();

        return $timeline;
    }

    // TYPE_POPUP_FORM_OPT_IN
    public static function recordPopupFormOptIn($subscriber, $form)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_POPUP_FORM_OPT_IN);

        $timeline->form_id = $form->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_API_SUBSCRIBED
    public static function recordApiSubscribed($subscriber, $customer)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_API_SUBSCRIBED);

        $timeline->customer_id = $customer->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_API_ADDED
    public static function recordApiAdded($subscriber, $customer)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_API_ADDED);

        $timeline->customer_id = $customer->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_SUBSCRIBED_BY_CUSTOMER
    public static function recordSubscribedByCustomer($subscriber, $customer)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_SUBSCRIBED_BY_CUSTOMER);

        $timeline->customer_id = $customer->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_UNSUBSCRIBED_BY_CUSTOMER
    public static function recordUnsubscribedByCustomer($subscriber, $customer)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_UNSUBSCRIBED_BY_CUSTOMER);

        $timeline->customer_id = $customer->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_COPIED_FROM
    public static function recordCopiedFrom($subscriber, $fromMailList)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_COPIED_FROM);

        $timeline->mail_list_id = $fromMailList->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_COPIED_TO
    public static function recordCopiedTo($subscriber, $toMailList)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_COPIED_TO);

        $timeline->mail_list_id = $toMailList->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_MOVED_FROM
    public static function recordMovedFrom($subscriber, $mailList)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_MOVED_FROM);

        $timeline->mail_list_id = $mailList->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_UNSUBSCRIBED_FROM_LIST_UNSUBSCRIBE_FORM
    public static function recordUnsubscribedFromListUnsubscribeForm($subscriber)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_UNSUBSCRIBED_FROM_LIST_UNSUBSCRIBE_FORM);

        $timeline->save();

        return $timeline;
    }

    // TYPE_OPENED_CAMPAIGN_EMAIL
    public static function recordOpenedCampaignEmail($subscriber, $campaign)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_OPENED_CAMPAIGN_EMAIL);

        $timeline->campaign_id = $campaign->id;

        $timeline->save();

        return $timeline;
    }

    // TYPE_CLICKED_CAMPAIGN_EMAIL
    public static function recordClickedCampaignEmail($subscriber, $campaign, $url)
    {
        $timeline = static::newDefault($subscriber, static::TYPE_CLICKED_CAMPAIGN_EMAIL);

        $timeline->campaign_id = $campaign->id;
        $timeline->url = $url;

        $timeline->save();

        return $timeline;
    }
}
