<?php

/**
 * Subscriber class.
 *
 * Model class for Subscriber
 *
 * LICENSE: This product includes software developed at
 * the Acelle Co., Ltd. (http://acellemail.com/).
 *
 * @category   MVC Model
 *
 * @author     N. Pham <n.pham@acellemail.com>
 * @author     L. Pham <l.pham@acellemail.com>
 * @copyright  Acelle Co., Ltd
 * @license    Acelle Co., Ltd
 *
 * @version    1.0
 *
 * @link       http://acellemail.com
 */

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Model;
use Acelle\Model\MailList;
use Acelle\Model\Setting;
use Acelle\Events\MailListSubscription;
use Acelle\Events\MailListUnsubscription;
use Acelle\Events\SubscriberTagsRemoved;
use Acelle\Library\StringHelper;
use DB;
use Exception;
use Acelle\Library\Traits\HasUid;
use Closure;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Schema;
use Acelle\Library\LaravelExtension\EloquentBuilder as ExtendedEloquentBuilder;
use Acelle\Events\SubscribersTagsUpdate;
use Acelle\Events\SubscribersAttributesUpdate;

class Subscriber extends Model
{
    use HasUid;

    public const STATUS_SUBSCRIBED = 'subscribed';
    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
    public const STATUS_BLACKLISTED = 'blacklisted';
    public const STATUS_SPAM_REPORTED = 'spam-reported';
    public const STATUS_UNCONFIRMED = 'unconfirmed';

    public const SUBSCRIPTION_TYPE_ADDED = 'added';
    public const SUBSCRIPTION_TYPE_DOUBLE_OPTIN = 'double';
    public const SUBSCRIPTION_TYPE_SINGLE_OPTIN = 'single';
    public const SUBSCRIPTION_TYPE_IMPORTED = 'imported';

    public const VERIFICATION_STATUS_DELIVERABLE = 'deliverable';
    public const VERIFICATION_STATUS_UNDELIVERABLE = 'undeliverable';
    public const VERIFICATION_STATUS_UNKNOWN = 'unknown';
    public const VERIFICATION_STATUS_RISKY = 'risky';
    public const VERIFICATION_STATUS_UNVERIFIED = 'unverified';

    protected $dates = ['unsubscribed_at'];

    public static $rules = [
        'email' => ['required', 'email:rfc,filter'],
    ];


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mail_list_id', 'email',
        'image',
    ];

    /**
     * Associations.
     *
     * @var object | collect
     */
    public function mailList()
    {
        return $this->belongsTo('Acelle\Model\MailList');
    }

    public function trackingLogs()
    {
        return $this->hasMany('Acelle\Model\TrackingLog');
    }

    public function autoTriggers()
    {
        return $this->hasMany('Acelle\Model\AutoTrigger');
    }

    public function unsubscribeLogs()
    {
        return $this->hasMany('Acelle\Model\UnsubscribeLog');
    }

    public function scopeUnverified($query)
    {
        return $query->whereNull('subscribers.verification_status');
    }

    public function scopeVerified($query)
    {
        return $query->whereNotNull('subscribers.verification_status');
    }

    public function scopeDeliverable($query)
    {
        return $query->where('subscribers.verification_status', self::VERIFICATION_STATUS_DELIVERABLE);
    }

    public function scopeDeliverableOrNotVerified($query)
    {
        return $query->whereRaw(sprintf(
            "(%s = '%s' OR %s IS NULL)",
            table('subscribers.verification_status'),
            self::VERIFICATION_STATUS_DELIVERABLE,
            table('subscribers.verification_status')
        ));
    }

    public function scopeUndeliverable($query)
    {
        return $query->where('subscribers.verification_status', self::VERIFICATION_STATUS_UNDELIVERABLE);
    }

    public function scopeUnknown($query)
    {
        return $query->where('subscribers.verification_status', self::VERIFICATION_STATUS_UNKNOWN);
    }

    public function scopeRisky($query)
    {
        return $query->where('subscribers.verification_status', self::VERIFICATION_STATUS_RISKY);
    }

    /**
     * Bootstrap any application services.
     */
    public static function boot()
    {
        parent::boot();

        // Create uid when creating list.
        static::creating(function ($item) {
            $item->uid = uniqid();
        });
    }

    /**
     * Get rules.
     *
     * @var array
     */
    public function getRules()
    {
        $rules = $this->mailList->getFieldRules();
        $item_id = isset($this->id) ? $this->id : 'NULL';
        $rules['EMAIL'] = $rules['EMAIL'].'|unique:subscribers,email,'.$item_id.',id,mail_list_id,'.$this->mailList->id;

        $rules['image'] = 'nullable|image';

        return $rules;
    }

    /**
     * Blacklist a subscriber.
     *
     * @return bool
     */
    public function sendToBlacklist($reason = null)
    {
        // blacklist all email
        self::where('email', $this->email)->update(['status' => self::STATUS_BLACKLISTED]);

        // create an entry in blacklists table
        $r = Blacklist::firstOrNew(['email' => $this->email]);
        $r->reason = $reason;
        $r->save();

        return true;
    }

    /**
     * Mark a subscriber/list as abuse-reported.
     *
     * @return bool
     */
    public function markAsSpamReported()
    {
        $this->status = self::STATUS_SPAM_REPORTED;
        $this->save();

        return true;
    }

    /**
     * Unsubscribe to the list.
     */
    public function unsubscribe($trackingInfo)
    {
        // Transaction safe
        DB::transaction(function () use ($trackingInfo) {
            // Update status
            $this->status = self::STATUS_UNSUBSCRIBED;
            $this->save();

            // Trigger events
            MailListUnsubscription::dispatch($this);

            // Create log
            $unsubscribeLog = new UnsubscribeLog();
            $unsubscribeLog->fill($trackingInfo);
            $unsubscribeLog->customer_id = $this->mailList->customer_id;
            $this->unsubscribeLogs()->save($unsubscribeLog);
        });
    }

    /**
     * Update fields from request.
     */
    public function updateFields($params)
    {
        $before = $this->getCustomAttributes();

        foreach ($this->mailList->getFields as $field) {
            // Thank you John Wigley and acorna.com team for pointing this out
            if (!isset($params[$field->tag])) {
                $params[$field->tag] = null;  // Fix for inability to clear checkboxes and multiselects, add in null elements for those missing from the form submission but defined as custom fields for that mailing list
            }
        }

        foreach ($params as $tag => $value) {
            $field = $this->mailList->getFieldByTag(str_replace('[]', '', $tag));
            if ($field) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $this->setFieldValue($field, $value);

                // update email attribute of subscriber
                if ($field->tag == 'EMAIL') {
                    $this->email = $value;
                }
            }
        }

        $this->save();

        $changes = $this->findDifferentCustomAttributes($before);
        SubscribersAttributesUpdate::dispatch($this, $changes, $before);
    }

    public function updateFields2($attributes)
    {
        $before = $this->getCustomAttributes();
        foreach ($attributes as $tag => $value) {
            $field = $this->mailList->getFieldByTag($tag);

            if (!is_null($field)) {
                $this->setFieldValue($field, $value);
            }
        }

        $this->save();

        $changes = $this->findDifferentCustomAttributes($before);
        SubscribersAttributesUpdate::dispatch($this, $changes, $before);
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($query, $request)
    {
        $query = $query->leftJoin('mail_lists', 'subscribers.mail_list_id', '=', 'mail_lists.id');

        if (isset($request)) {
            // Keyword
            if (!empty(trim($request->keyword))) {
                foreach (explode(' ', trim($request->keyword)) as $keyword) {
                    $query = $query->where(function ($q) use ($keyword) {
                        $q->orwhere('subscribers.email', 'like', '%'.$keyword.'%');
                    });
                }
            }

            // filters
            $filters = $request->filters;
            if (!empty($filters)) {
                if (!empty($filters['status'])) {
                    $query = $query->where('subscribers.status', '=', $filters['status']);
                }
                if (!empty($filters['verification_result'])) {
                    if ($filters['verification_result'] == 'unverified') {
                        $query = $query->whereNull('subscribers.verification_status');
                    } else {
                        $query = $query->where('subscribers.verification_status', '=', $filters['verification_result']);
                    }
                }
            }

            // outside filters
            if (!empty($request->status)) {
                $query = $query->where('subscribers.status', '=', $request->status);
            }
            if (!empty($request->verification_result)) {
                if ($request->verification_result == 'unverified') {
                    $query = $query->whereNull('subscribers.verification_status');
                } else {
                    $query = $query->where('subscribers.verification_status', '=', $request->verification_result);
                }
            }

            // Open
            if ($request->open == 'yes') {
                $query = $query->whereExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('open_logs')
                        ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Not Open
            if ($request->open == 'no') {
                $query = $query->whereNotExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('open_logs')
                        ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Click
            if ($request->click == 'yes') {
                $query = $query->whereExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('click_logs')
                        ->join('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Not Click
            if ($request->click == 'no') {
                $query = $query->whereNotExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('click_logs')
                        ->join('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }
        }

        return $query;
    }

    /**
     * Get all languages.
     *
     * @return collect
     */
    public static function search($request, $customer = null)
    {
        $query = self::select('subscribers.*');

        // Filter by customer
        if (!isset($customer)) {
            $customer = $request->user()->customer;
        }
        $query = $query->where('mail_lists.customer_id', '=', $customer->id);

        // Filter
        $query = self::filter($query, $request);

        // Order
        if (isset($request->sort_order)) {
            $query = $query->orderBy($request->sort_order, $request->sort_direction);
        }

        return $query;
    }

    /**
     * Get field value by list field.
     *
     * @return value
     */
    public function getValueByField($field)
    {
        $value = $this[$field->custom_field_name];

        if ($field->isDate()) {
            return $value == '1900-01-01' ? '' : $value;
        } else {
            return $value;
        }
    }

    public function getValueByTag($tag)
    {
        $field = $this->mailList->fields()->where('tag', $tag)->first();

        if (is_null($field)) {
            return '';
        } else {
            return $this->getValueByField($field);
        }
    }

    public function getCustomAttributes()
    {
        $attributes = [];
        $fields = $this->mailList->fields;
        foreach ($fields as $field) {
            $attributes[$field->uid] = $this->getValueByField($field);
        }

        $attributes['tags'] = $this->getTags();

        return $attributes;
    }

    public function findDifferentCustomAttributes(array $attributes)
    {
        $diffs = [];
        $myAttributes = $this->getCustomAttributes();
        foreach ($myAttributes as $key => $value) {
            if (array_key_exists($key, $attributes) && $attributes[$key] != $value) {
                $diffs[$key] = $value;
            }
        }

        return $diffs;
    }

    /**
     * Set field.
     *
     * @return value
     */
    public function setFieldValue($field, $value)
    {
        if ($field->isDate()) {
            $value = $this->mailList->customer->parseDateTime($value, true)->format(config('custom.date_format'));
        }
        $this[$field->custom_field_name] = $value;
    }

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;

    /**
     * Create customer action log.
     *
     * @param string   $cat
     * @param Customer $customer
     * @param array    $add_datas
     */
    public function log($name, $customer, $add_datas = [])
    {
        $data = [
                'id' => $this->id,
                'email' => $this->email,
                'list_id' => $this->mail_list_id,
                'list_name' => $this->mailList->name,
        ];

        $data = array_merge($data, $add_datas);

        \Acelle\Model\Log::create([
                                'customer_id' => $customer->id,
                                'type' => 'subscriber',
                                'name' => $name,
                                'data' => json_encode($data),
                            ]);
    }

    /**
     * Copy to list.
     *
     * @param MailList $list
     */
    public function doCopy(MailList $list, Closure $duplicateCallback = null)
    {
        // find exists
        $copy = $list->subscribers()->where('email', '=', $this->email)->first();

        if (!is_null($copy)) {
            if (!is_null($duplicateCallback)) {
                $duplicateCallback($this);
            }

            return null;
        }

        // Actually copy
        $subscriber = self::find($this->id);
        $copy = $subscriber->replicate();
        $copy->uid = uniqid();
        $copy->mail_list_id = $list->id;
        $copy->save();


        foreach ($this->mailList->fields as $field) {
            $copy->setFieldValue($field, $this->getValueByField($field));
        }

        return $copy;
    }

    public function copy(MailList $list, Closure $duplicateCallback = null)
    {
        $copy = $this->doCopy($list, $duplicateCallback);

        // has copy action
        if ($copy) {
            // Timeline record
            \Acelle\Model\Timeline::recordCopiedTo($this, $list);
            \Acelle\Model\Timeline::recordCopiedFrom($copy, $this->mailList);
        }
    }

    /**
     * Move to list.
     *
     * @param MailList $list
     */
    public function move($list)
    {
        $copy = $this->doCopy($list);

        // has move action
        if ($copy) {
            // Timeline record
            \Acelle\Model\Timeline::recordMovedFrom($copy, $this->mailList);
        }

        // delete current one
        $this->delete();
    }

    /**
     * Get tracking log.
     *
     * @param MailList $list
     */
    public function trackingLog($campaign)
    {
        $query = \Acelle\Model\TrackingLog::where('tracking_logs.subscriber_id', '=', $this->id);
        $query = $query->where('tracking_logs.campaign_id', '=', $campaign->id)->orderBy('created_at', 'desc')->first();

        return $query;
    }

    /**
     * Get all subscriber's open logs.
     *
     * @param MailList $list
     */
    public function openLogs($campaign = null)
    {
        $query = \Acelle\Model\OpenLog::leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'open_logs.message_id')
            ->where('tracking_logs.subscriber_id', '=', $this->id);

        if (isset($campaign)) {
            $query = $query->where('tracking_logs.campaign_id', '=', $campaign->id);
        }

        return $query;
    }

    /**
     * Get last open.
     *
     * @param MailList $list
     */
    public function lastOpenLog($campaign = null)
    {
        $query = $this->openLogs($campaign);

        $query = $query->orderBy('open_logs.created_at', 'desc')->first();

        return $query;
    }

    /**
     * Get all subscriber's click logs.
     *
     * @param MailList $list
     */
    public function clickLogs($campaign = null)
    {
        $query = \Acelle\Model\ClickLog::leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'click_logs.message_id')
            ->where('tracking_logs.subscriber_id', '=', $this->id);

        if (isset($campaign)) {
            $query = $query->where('tracking_logs.campaign_id', '=', $campaign->id);
        }

        return $query;
    }

    /**
     * Get last click.
     *
     * @param MailList $list
     */
    public function lastClickLog($campaign = null)
    {
        $query = $this->clickLogs();
        $query = $query->orderBy('click_logs.created_at', 'desc')->first();

        return $query;
    }

    /**
     * Is overide copy/move subscriber.
     *
     * return array
     */
    public static function copyMoveExistSelectOptions()
    {
        return [
            ['text' => trans('messages.update_if_subscriber_exists'), 'value' => 'update'],
            ['text' => trans('messages.keep_if_subscriber_exists'), 'value' => 'keep'],
        ];
    }

    /**
     * Verify subscriber email address using a given service.
     */
    public function verify($verifier)
    {
        list($status, $rawResponse) = $verifier->verify($this->email);
        $this->verification_status = $status;
        $this->last_verification_at = Carbon::now();
        $this->last_verification_by = $verifier->name;
        $this->last_verification_result = $rawResponse;
        $this->save();
        return $this;
    }

    public function setVerificationStatus($status)
    {
        // note: status must be one of the pre-defined list: see related constants
        $this->verification_status = $status;
        $this->last_verification_at = Carbon::now();
        $this->last_verification_by = 'ADMIN';
        $this->last_verification_result = 'Manually set';
        $this->save();
        return $this;
    }

    /**
     * Reset subscriber verification.
     */
    public function resetVerification()
    {
        $this->verification_status = null;
        $this->last_verification_at = null;
        $this->last_verification_by = null;
        $this->last_verification_result = null;
        $this->save();
    }

    public function getImagePath()
    {
        $path = storage_path('app/subscriber/');

        // create if not exist
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        //
        return join_paths($path, $this->uid.'.jpg');
    }

    public function getImageOriginPath()
    {
        return $this->getImagePath() . '.origin.jpg';
    }

    /**
     * Upload and resize avatar.
     *
     * @var string
     *
     * @return string
     */
    public function uploadImage($file)
    {
        $path = $this->getImagePath();
        $originPath = $this->getImageOriginPath();

        // File name: avatar
        $filename = basename($originPath);

        // The base dir: /storage/app/users/000000/home/
        $dirname = dirname($originPath);

        // save to server
        $file->move($dirname, $filename);

        // create thumbnails
        $img = \Image::make($originPath);

        // resize image
        $img->resize(500, 500, function ($constraint) {
            $constraint->aspectRatio();
            $constraint->upsize();
        })->save($originPath);

        // default size overwrite
        $img->fit(120, 120)->save($path);

        return $path;
    }

    /**
     * Remove thumb path.
     */
    public function removeImage()
    {
        if (!empty($this->uid)) {
            $path = storage_path('app/subscriber/'.$this->uid);
            if (is_file($path)) {
                unlink($path);
            }
            if (is_file($path.'.jpg')) {
                unlink($path.'.jpg');
            }
        }
    }

    /**
     * Check if the subscriber is listed in the Blacklist database.
     */
    public function isListedInBlacklist()
    {
        $globalCheckQuery = Blacklist::global()->where('email', '=', $this->email);
        $localCheckQuery = Blacklist::where('email', '=', $this->email)->where('customer_id', $this->mailList->customer_id);

        if (Setting::isYes('blacklist.use_global_blacklist')) {
            return $globalCheckQuery->exists() || $localCheckQuery->exists();
        } else {
            return $localCheckQuery->exists();
        }
    }

    public function getFullName($default = null, $reload = true)
    {
        if ($reload && $this->id) {
            $this->refresh();
        }

        $lastNameFirst = get_localization_config('show_last_name_first', $this->mailList->customer->getLanguageCode());

        if ($lastNameFirst) {
            $full = trim($this->getValueByTag('LAST_NAME').' '.$this->getValueByTag('FIRST_NAME'));
        } else {
            $full = trim($this->getValueByTag('FIRST_NAME').' '.$this->getValueByTag('LAST_NAME'));
        }


        if (empty($full)) {
            return $default;
        } else {
            return $full;
        }
    }

    public function getFullNameOrEmail()
    {
        $full = $this->getFullName();
        if (empty($full)) {
            return $this->email;
        } else {
            return $full;
        }
    }

    /**
     * Is the subscriber active?
     */
    public function isActive()
    {
        return $this->status == self::STATUS_SUBSCRIBED;
    }

    /**
     * Get tags.
     */
    public function getTags(): array
    {
        // Notice: json_decode() returns null if input is null or empty

        if (is_null($this->tags)) {
            return [];
        }

        return json_decode($this->tags, true) ?: [];
    }

    /**
     * Get tags.
     */
    public function getTagOptions()
    {
        $arr = [];
        foreach ($this->getTags() as $tag) {
            $arr[] = ['text' => $tag, 'value' => $tag];
        }

        return $arr;
    }

    /**
     * Add tags.
     */
    public function addTags($arr)
    {
        $tags = $this->getTags();

        $nTags = array_values(array_unique(array_merge($tags, $arr)));

        $this->tags = json_encode($nTags);
        $this->save();

        SubscribersTagsUpdate::dispatch($this);
    }

    /**
     * Add tags.
     */
    public function updateTags(array $newTags, $merge = false)
    {
        // remove trailing space
        array_walk($newTags, function (&$val) {
            $val = trim($val);
        });

        // remove empty tag
        $newTags = array_filter($newTags, function (&$val) {
            return !empty($val);
        });

        if ($merge == true) {
            $currentTags = $this->getTags();
            $newTags = array_values(array_unique(array_merge($currentTags, $newTags)));
        }

        // Without JSON_UNESCAPED_UNICODE specified
        // Results of json_encode(['русский']) may look like this
        //
        //     ["\u0440\u0443\u0441\u0441\u043a\u0438\u0439"]
        //
        // which cannot be searched for
        //

        $before = $this->getCustomAttributes();

        $this->tags = json_encode($newTags, JSON_UNESCAPED_UNICODE);
        $this->save();

        SubscribersTagsUpdate::dispatch($this);
        $changes = $this->findDifferentCustomAttributes($before);
        SubscribersAttributesUpdate::dispatch($this, $changes, $before);
    }

    /**
     * Remove tag.
     */
    public function removeTags(array $tagsToRemove = [])
    {
        $oldTags = $this->getTags();
        $tags = $oldTags;

        $removedTags = [];
        foreach ($tagsToRemove as $tag) {
            if (($key = array_search($tag, $tags)) !== false) {
                unset($tags[$key]);
                $this->tags = json_encode($tags);
                $removedTags[] = $tag;
            }
        }

        if (!empty($removedTags)) {
            $this->save();
            SubscriberTagsRemoved::dispatch($this, $removedTags);
        } else {
            // nothing change
        }
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public function scopeFilter($query, $request)
    {
        if (isset($request)) {
            // filters
            $filters = $request->filters;
            if (!empty($filters)) {
                if (!empty($filters['status'])) {
                    $query = $query->where('subscribers.status', '=', $filters['status']);
                }
                if (!empty($filters['verification_result'])) {
                    if ($filters['verification_result'] == 'unverified') {
                        $query = $query->whereNull('subscribers.verification_status');
                    } else {
                        $query = $query->where('subscribers.verification_status', '=', $filters['verification_result']);
                    }
                }
            }

            // outside filters
            if (!empty($request->status)) {
                $query = $query->where('subscribers.status', '=', $request->status);
            }
            if (!empty($request->verification_result)) {
                $query = $query->where('subscribers.verification_status', '=', $request->verification_result);
            }

            // Open
            if ($request->open == 'yes') {
                $query = $query->whereExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('open_logs')
                        ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Not Open
            if ($request->open == 'no') {
                $query = $query->whereNotExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('open_logs')
                        ->join('tracking_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Click
            if ($request->click == 'yes') {
                $query = $query->whereExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('click_logs')
                        ->join('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }

            // Not Click
            if ($request->click == 'no') {
                $query = $query->whereNotExists(function ($q) {
                    $q->select(\DB::raw(1))
                        ->from('click_logs')
                        ->join('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')
                        ->whereRaw(table('tracking_logs').'.subscriber_id = '.table('subscribers').'.id');
                });
            }
        }

        return $query;
    }

    /**
     * Get all languages.
     *
     * @return collect
     */
    public function scopeSearch($query, $keyword, $listFields = [])
    {
        // Keyword
        if (!empty(trim($keyword))) {
            foreach (explode(' ', trim($keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword, $listFields) {
                    $q->orwhere('subscribers.email', 'like', '%'.$keyword.'%');

                    foreach ($listFields as $f) {
                        $q->orwhere("subscribers.{$f->custom_field_name}", 'LIKE', '%'.$keyword.'%');
                        ;
                    }

                });
            }
        }

        return $query;
    }

    public function scopeSubscribed($query)
    {
        return $query->where('subscribers.status', self::STATUS_SUBSCRIBED);
    }

    public function isSubscribed()
    {
        return $this->status == self::STATUS_SUBSCRIBED;
    }

    public function isUnsubscribed()
    {
        return $this->status == self::STATUS_UNSUBSCRIBED;
    }

    public function getHistory()
    {
        $openLogs = table('open_logs');
        $clickLogs = table('click_logs');
        $subscribeLogs = table('subscribe_logs');
        $subscribers = table('subscribers');
        $mailLists = table('mail_lists');
        $campaigns = table('campaigns');
        $trackingLogs = table('tracking_logs');

        $sql = "
            SELECT subscriber_id, activity, list_id, list_name, campaign_id, campaign_name, at
            FROM
            (
                SELECT t.subscriber_id, 'open' as activity, null as list_id, null as list_name, t.campaign_id, c.name as campaign_name, open.created_at as at
                FROM {$openLogs} open
                JOIN {$trackingLogs} t on open.message_id = t.message_id
                JOIN {$subscribers} s on s.id = t.subscriber_id
                JOIN {$campaigns} c on c.id  = t.campaign_id
                WHERE s.email = '{$this->email}'
            ) AS open

            UNION
            (
                SELECT t.subscriber_id, 'click' as activity, null as list_id, null as list_name, t.campaign_id, c.name as campaign_name, click.created_at as at
                FROM {$clickLogs} click
                JOIN {$trackingLogs} t on click.message_id = t.message_id
                JOIN {$subscribers} s on s.id = t.subscriber_id
                JOIN {$campaigns} c on c.id  = t.campaign_id
                WHERE s.email = '{$this->email}'
            )

            UNION
            (
                SELECT s.id AS subscriber_id, 'subscribe' AS activity, l.id as list_id, l.name as list_name, null AS campaign_id, null AS campaign_name, s.created_at as at
                FROM {$subscribers} s
                JOIN {$mailLists} l on l.id  = s.mail_list_id
                WHERE s.email = '{$this->email}'
            )

            ORDER BY at DESC;
        ";

        $result = DB::select($sql);

        return json_decode(json_encode($result), true);
    }

    public function scopeSearchByEmail($query, $email)
    {
        return $query->where('subscribers.email', $email);
    }

    /**
     * assgin values.
     */
    public static function assginValues($subscribers, $request)
    {
        $field = Field::findByUid($request->field_uid);

        if ($request->assign_type == 'single') {
            $rules = [
                'single_value' => 'required',
            ];
        } else {
            $rules = [
                'list_value' => 'required',
            ];
        }

        // make validator
        $validator = \Validator::make($request->all(), $rules);

        // redirect if fails
        if ($validator->fails()) {
            return $validator;
        }

        // do assign
        if ($request->assign_type == 'single') {
            // do assign a value: $request->single_value
            foreach ($subscribers->get() as $subscriber) {
                $subscriber->setFieldValue($field, $request->single_value);
                $subscriber->save();
            }
        } else {
            // do assign a list: $request->list_value
        }

        return $validator;
    }


    // Confirm a subscription via double opt-in form
    public function confirm()
    {
        $this->status = self::STATUS_SUBSCRIBED;
        $this->save();

        MailListSubscription::dispatch($this);
    }

    public function scopeUnsubscribed($query)
    {
        return $query->where('status', '=', self::STATUS_UNSUBSCRIBED);
    }

    // ONE CLICK UNSUBSCRIBE
    public function generateUnsubscribeUrl($messageId, $absoluteUrl = true)
    {
        $url = route('unsubscribeUrl', [
            'message_id' => StringHelper::base64UrlEncode($messageId),
            'subscriber' => $this->id
        ], $absoluteUrl);

        return $url;
    }

    // Load a form, click Submit to actually opt out
    public function generateUnsubscribeForm($messageId, $absoluteUrl = true)
    {
        $url = route('unsubscribeForm', [
            'message_id' => StringHelper::base64UrlEncode($messageId),
            'id' => $this->id
        ], $absoluteUrl);

        return $url;
    }

    public function generateUpdateProfileUrl()
    {
        return route('updateProfileUrl', ['list_uid' => $this->mailList->uid, 'id' => $this->id, 'customer_uid' => $this->mailList->customer->uid]);
    }

    // Change status to SUBSCRIBED
    // @important: need subscription log in the future?
    public function subscribe()
    {
        $this->status = self::STATUS_SUBSCRIBED;
        $this->save();

        MailListSubscription::dispatch($this);
    }

    public function scopeSimpleSearch($query, $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        $cleanKeyword = preg_replace('/[^a-z0-9_\.@]+/i', ' ', $keyword);

        return $query->where(function ($query) use ($cleanKeyword) {
            $query->where('subscribers.email', 'LIKE', "%{$cleanKeyword}%");
        });
    }

    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Check if the email address is deliverable.
     *
     * @return bool
     */
    public function isDeliverable()
    {
        return $this->verification_result == self::VERIFICATION_STATUS_DELIVERABLE;
    }

    /**
     * Check if the email address is undeliverable.
     *
     * @return bool
     */
    public function isUndeliverable()
    {
        return $this->verification_result == self::VERIFICATION_STATUS_UNDELIVERABLE;
    }

    /**
     * Check if the email address is risky.
     *
     * @return bool
     */
    public function isRisky()
    {
        return $this->verification_result == self::VERIFICATION_STATUS_RISKY;
    }

    /**
     * Check if the email address is unknown.
     *
     * @return bool
     */
    public function isUnknown()
    {
        return $this->verification_result == self::VERIFICATION_STATUS_UNKNOWN;
    }

    /**
     * Email verification result types select options.
     *
     * @return array
     */
    public static function getVerificationStates()
    {
        return [
            ['value' => self::VERIFICATION_STATUS_DELIVERABLE, 'text' => trans('messages.email_verification_result_deliverable')],
            ['value' => self::VERIFICATION_STATUS_UNDELIVERABLE, 'text' => trans('messages.email_verification_result_undeliverable')],
            ['value' => self::VERIFICATION_STATUS_UNKNOWN, 'text' => trans('messages.email_verification_result_unknown')],
            ['value' => self::VERIFICATION_STATUS_RISKY, 'text' => trans('messages.email_verification_result_risky')],
            ['value' => self::VERIFICATION_STATUS_UNVERIFIED, 'text' => trans('messages.email_verification_result_unverified')],
        ];
    }

    public static function getByListsAndSegments($connection, ...$segmentsOrLists)
    {
        if (empty($segmentsOrLists)) {
            // this is a trick for returning an empty builder
            return static::on($connection)->limit(0);
        }

        $query = static::on($connection)->select('subscribers.*');

        // Get subscriber from mailist and segment
        $conditions = [];
        foreach ($segmentsOrLists as $listOrSegment) {
            if ($listOrSegment instanceof Segment) {
                // Segment
                $conds = $listOrSegment->getSubscribersConditions();

                // Break, otherwise it causes an error like:
                // Illuminate\Database\QueryException with message 'SQLSTATE[42000]: Syntax error or access violation: 1064 You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near ')))' at line 1 (SQL: select count(*) as aggregate from `subscribers` where ((subscribers.mail_list_id = 33 AND ())))'
                if (is_null($conds)) {
                    continue;
                }

                // IMPORTANT: segment condition does not include list_id constraints, so we have to add it to make sure only the segment's list is considered
                $conds['conditions'] = '('.table('subscribers.mail_list_id').' = '.$listOrSegment->mail_list_id.' AND ('.$conds['conditions'].'))';
                $conditions[] = $conds['conditions'];
            } elseif ($listOrSegment instanceof MailList) {
                // Entire list
                $listId = $listOrSegment->id;
                $conditions[] = '('.table('subscribers.mail_list_id').' = '.$listId.')';
            } else {
                throw new Exception('Object must be Segment or MailList');
            }
        }

        if (!empty($conditions)) {
            $query = $query->whereRaw('('.implode(' OR ', $conditions).')');
        }

        return $query;
    }

    public static function scopeByEmail($query, $email)
    {
        $query = $query->where('email', $email);
    }

    public static function addCustomFieldIfNotExist($fieldName)
    {
        $tableName = (new static())->getTable();
        if (!Schema::hasColumn($tableName, $fieldName)) {

            Schema::table($tableName, function (Blueprint $table) use ($fieldName) {
                $table->mediumText($fieldName)->nullable();
            });

        }
    }

    /**
     * assgin tags.
     */
    public static function addSubscribersTags($subscribers, $tags)
    {
        // make validator
        $validator = \Validator::make([
            'tags' => $tags,
        ], [
            'tags' => 'required|array',
        ]);

        // redirect if fails
        if ($validator->fails()) {
            return $validator;
        }

        // add tags
        foreach ($subscribers->get() as $subscriber) {
            $subscriber->addTags($tags);
        }

        return $validator;
    }

    // $updates is a PHP array equiv. to:
    // [{"field_uid":"668a855841b1d","value":"Field Value"},{"field_uid":"668a855843d3b","value":"Field Value"}]
    public function bulkUpdate($updates)
    {
        foreach ($updates as $attr) {
            $fieldUid = $attr['field_uid'];
            $field = Field::findByUid($fieldUid);
            $fieldValue = $attr['value'];

            // for example: $this->custom_101 = 'New First Name';
            $this[$field->custom_field_name] = $fieldValue;
        }

        $this->save();
    }

    // @OVERWRITE the parent method
    public function newEloquentBuilder($query)
    {
        return new ExtendedEloquentBuilder($query);
    }

    //
    public function hasTags(array $tags)
    {
        $intersected = array_intersect(array_map('strtolower', $this->getTags()), array_map('strtolower', $tags));
        return (!empty($intersected));
    }

    public function timelines()
    {
        return $this->hasMany(Timeline::class);
    }

    public function readTimelines()
    {
        return $this->timelines()->orderBy('created_at', 'desc')->limit(100)->get();
    }
}
