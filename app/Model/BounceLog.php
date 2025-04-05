<?php

/**
 * BounceLog class.
 *
 * Model class for bounce logs
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
use Exception;
use Validator;

class BounceLog extends Model
{
    // Bounce types might have other values like: Permanent, Transient...
    // See: https://docs.aws.amazon.com/ses/latest/dg/notification-contents.html#bounce-types
    public const HARD = 'hard';
    public const SOFT = 'soft';
    public const UNKNOWN = 'unknown';

    protected $casts = [
        'raw' => 'object',
    ];

    /**
     * Associations.
     *
     * @var object | collect
     */
    public function trackingLog()
    {
        return $this->belongsTo('Acelle\Model\TrackingLog', 'message_id', 'message_id');
    }

    // Deprecated, use record() method instead
    public static function createFromRequest($params)
    {
        $rules = [
            'message_id' => 'required',
            'type' => 'required|in:sent,bounced,reported,failed'
        ];

        $messages = [
            'type.in' => 'Allowed values for type include: sent | bounced | reported | failed'
        ];

        $validator = Validator::make($params, $rules, $messages);

        // redirect if fails
        if ($validator->fails()) {
            return [null, $validator];
        }

        $trackingLog = TrackingLog::where('message_id', $params['message_id'])->first();

        if (empty($trackingLog)) {
            // throw new Exception("No message found with Message-ID: ".$params['message_id']);
            $rules = [
                'message_id' => [
                    function ($attribute, $value, $fail) use ($params) {
                        $fail("No message found with Message-ID: ".$params['message_id']);
                    }
                ]
            ];

            // Make it (surely) fail
            $validator = Validator::make($params, $rules);
            return [null, $validator];
        }

        $bounceLog = new static();
        $bounceLog->tracking_log_id = $trackingLog->id;
        $bounceLog->customer_id = $trackingLog->customer_id;
        $bounceLog->message_id = $params['message_id'];
        $bounceLog->runtime_message_id = $trackingLog->runtime_message_id;
        $bounceLog->bounce_type = array_key_exists('bounce_type', $params) ? $params['bounce_type'] : static::HARD; // @TODO fill in the NULL value here
        $bounceLog->raw = $params['description'] ?? 'N/A';
        $bounceLog->save();

        return [$bounceLog, $validator];
    }

    public static function recordHardBounce($runtimeMessageId, $raw, $logCallback = null, $throwMsgNotFoundException = false)
    {
        if (is_null($runtimeMessageId)) {
            throw new Exception("message_id must not be null");
        }

        $bounceLog = new static();
        $bounceLog->runtime_message_id = $runtimeMessageId;

        if (is_null($logCallback)) {
            $logCallback = function ($message) {
                // do nothing
            };
        }

        // retrieve the associated tracking log in Acelle
        $trackingLog = TrackingLog::where('runtime_message_id', $bounceLog->runtime_message_id)->first();

        if (is_null($trackingLog)) {
            $trackingLog = TrackingLog::where('message_id', $bounceLog->runtime_message_id)->first();
        }

        if ($trackingLog) {
            $bounceLog->message_id = $trackingLog->message_id;
            $bounceLog->customer_id = $trackingLog->customer_id;
            $bounceLog->tracking_log_id = $trackingLog->id;
        } else {
            $logCallback("Cannot find a tracking log record with runtime_message_id of #{$runtimeMessageId}");

            if ($throwMsgNotFoundException) {
                throw new Exception("Cannot find a tracking log record with runtime_message_id of #{$runtimeMessageId}");
            }
        }

        // SendGrid only notifies in case of HARD bounce
        $bounceLog->bounce_type = self::HARD;
        $bounceLog->raw = $raw; // notice that {$raw} might contain more than one events
        $bounceLog->save();

        // add subscriber's email to blacklist
        $subscriber = $bounceLog->findSubscriberByRuntimeMessageId();
        if ($subscriber) {
            $subscriber->sendToBlacklist($bounceLog->raw);
            $logCallback("Bounce recorded for runtime_message_id '$bounceLog->runtime_message_id' and {$subscriber->email} is blacklisted!");
        } else {
            $logCallback("Bounce recorded for runtime_message_id '$bounceLog->runtime_message_id' although it is not associated with any tracking log.");
        }

        return $bounceLog;
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        return self::select('bounce_logs.*');
    }

    /**
     * Find corresponding subscriber by 'runtime_message_id'.
     *
     * @return mixed
     */
    public function findSubscriberByRuntimeMessageId()
    {
        $trackingLog = TrackingLog::where('runtime_message_id', $this->runtime_message_id)->first();
        if ($trackingLog) {
            return $trackingLog->subscriber;
        } else {
            return;
        }
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $query = self::select('bounce_logs.*');
        $query = $query->leftJoin('tracking_logs', 'bounce_logs.message_id', '=', 'tracking_logs.message_id');
        $query = $query->leftJoin('subscribers', 'subscribers.id', '=', 'tracking_logs.subscriber_id');
        $query = $query->leftJoin('campaigns', 'campaigns.id', '=', 'tracking_logs.campaign_id');
        // Cross DB reference not supported
        // $query = $query->leftJoin('sending_servers', 'sending_servers.id', '=', 'tracking_logs.sending_server_id');
        $query = $query->leftJoin('customers', 'customers.id', '=', 'tracking_logs.customer_id');
        $query = $query->where('tracking_logs.id', '!=', null);

        // Keyword
        if (!empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('campaigns.name', 'like', '%'.$keyword.'%')
                        ->orwhere('bounce_logs.bounce_type', 'like', '%'.$keyword.'%')
                        ->orwhere('bounce_logs.raw', 'like', '%'.$keyword.'%')
                        // ->orwhere('sending_servers.name', 'like', '%'.$keyword.'%')
                        ->orwhere('subscribers.email', 'like', '%'.$keyword.'%');
                });
            }
        }

        // filters
        $filters = $request->all();
        if (!empty($filters)) {
            if (!empty($filters['campaign_uid'])) {
                $query = $query->where('campaigns.uid', '=', $filters['campaign_uid']);
            }
        }

        return $query;
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public static function search($request, $campaign = null)
    {
        $customer_id = $request->user()->customer->id;
        $query = self::filter($request);

        if (isset($campaign)) {
            if($campaign->customer_id != $customer_id){
                $query = $query->join('mail_lists', 'mail_lists.id', '=', 'subscribers.mail_list_id')->where('mail_lists.customer_id', '=', $customer_id);
            }
            $query = $query->where('tracking_logs.campaign_id', '=', $campaign->id);
        }

        $query = $query->orderBy($request->sort_order, $request->sort_direction);

        return $query;
    }

    public function isHard()
    {
        return $this->bounce_type == self::HARD;
    }

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;
}
