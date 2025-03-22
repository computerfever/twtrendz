<?php

/**
 * ClickLog class.
 *
 * Model class for click logs
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
use Acelle\Events\CampaignUpdated;
use Acelle\Library\StringHelper;
use Exception;
use Illuminate\Support\Facades\Validator;

class ClickLog extends Model
{
    public static function createFromRequest($request)
    {
        $url = StringHelper::base64UrlDecode($request->url);

        try {
            self::validateUrl($url); // throw an exception if failed
        } catch (Exception $ex) {
            // just ignore and let users go
            // the 'url' validation of Laravel does not work with UTF8
            // For example: https://algeriestore.com/content/Algérie-Store-Catalogue-FR.pdf
            return [$url, null];
        }

        $messageId = StringHelper::base64UrlDecode($request->message_id);

        if (is_null($messageId)) {
            // Preview email does not have message ID
            return [$url, null];
        }

        $customerUid = \Acelle\Library\StringHelper::extractCustomerUidFromMessageId($messageId);
        $customer = \Acelle\Model\Customer::findByUid($customerUid);
        if (!is_null($customer)) {
            $customer->setUserDbConnection();
        }

        $trackingLog = TrackingLog::where('message_id', $messageId)->first();
        if (is_null($trackingLog)) {
            return [$url, null];
        }

        $log = new self();
        $log->tracking_log_id = $trackingLog->id;
        $log->customer_id = $trackingLog->customer_id;
        $log->message_id = $messageId;
        $log->url = $url;
        $log->user_agent = array_key_exists('HTTP_USER_AGENT', $_SERVER) ? $_SERVER['HTTP_USER_AGENT'] : null;

        try {
            $location = IpLocation::add($request->ip());
            $log->ip_address = $location->ip_address;
        } catch (Exception $ex) {
            // Then no ip_address information
        }

        // Save anyway
        $log->save();

        // Just in case an open cannot be recorded (it is very likely the case, due to Gmail proxy/cache)
        // Make it make sense! there is at least one OPEN
        // @todo what about open callbacks?
        $openLog = $log->createRelatedOpenLogIfNotExist();

        // Do not trigger cache update if campaign is running
        if ($log->trackingLog && !is_null($log->trackingLog->campaign)) {
            if (!$log->trackingLog->campaign->isSending()) {
                event(new CampaignUpdated($log->trackingLog->campaign));
            }
        }

        return [$url, $log];
    }


    public static function isUrlBlacklisted($url)
    {
        $path = base_path('blacklisted_click_url');
        $blacklist = preg_split('/[\r\n]+/', trim(file_get_contents($path)));

        return in_array($url, $blacklist);
    }

    public function createRelatedOpenLogIfNotExist()
    {
        $openLog = OpenLog::where('message_id', $this->message_id)->exists();

        if (!$openLog) {
            OpenLog::createFromMessageId($this->message_id, $this->ip_address, $this->user_agent);
        }
    }

    private static function validateUrl($url)
    {
        $value = [ 'url' => $url ];
        $rules = [ 'url' => 'required|url' ];
        $validator = Validator::make($value, $rules);

        if ($validator->fails()) {
            throw new Exception('Invalid URL: '.$url);
        }
    }

    /**
     * Associations.
     *
     * @var object | collect
     */
    public function trackingLog()
    {
        return $this->belongsTo('Acelle\Model\TrackingLog', 'message_id', 'message_id');
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        return self::select('click_logs.*');
    }

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $query = self::select('click_logs.*');
        $query = $query->leftJoin('tracking_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id');
        $query = $query->leftJoin('subscribers', 'subscribers.id', '=', 'tracking_logs.subscriber_id');
        $query = $query->leftJoin('campaigns', 'campaigns.id', '=', 'tracking_logs.campaign_id');

        // Cross DB reference not supported
        // $query = $query->leftJoin('sending_servers', 'sending_servers.id', '=', 'tracking_logs.sending_server_id');
        $query = $query->leftJoin('customers', 'customers.id', '=', 'tracking_logs.customer_id');

        // Keyword
        if (!empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('campaigns.name', 'like', '%'.$keyword.'%')
                        ->orwhere('click_logs.ip_address', 'like', '%'.$keyword.'%')
                        ->orwhere('click_logs.url', 'like', '%'.$keyword.'%')
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
        $query = self::filter($request);

        if (isset($campaign)) {

            $customer_id = $request->user()->customer->id;

            $query = $query->where('tracking_logs.campaign_id', '=', $campaign->id);
            
            if($campaign->admin == 1 AND $campaign->customer_id != $customer_id){
                $query->join('mail_lists', 'mail_lists.id', '=', 'subscribers.mail_list_id')->where('mail_lists.customer_id', '=', $customer_id);
            }

        }

        $query = $query->orderBy($request->sort_order, $request->sort_direction);

        return $query;
    }

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;
}
