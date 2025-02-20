<?php

/**
 * Customer class.
 *
 * Model class for customer
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
use Acelle\Model\Customer as MasterCustomer;
use Acelle\Library\Traits\HasCache;

class LocalCustomer extends Customer
{
    use HasCache;

    protected $connection = 'set-it-to-a-dummy-value-to-avoid-falling-back-to-the-connection-of-the-caller-class';

    protected $table = 'customers';

    public function mailLists()
    {
        return $this->hasMany('Acelle\Model\MailList', 'customer_id');
    }

    public function lists()
    {
        return $this->hasMany('Acelle\Model\MailList', 'customer_id');
    }

    public function campaigns()
    {
        return $this->hasMany('Acelle\Model\Campaign', 'customer_id');
    }

    public function sentCampaigns()
    {
        return $this->hasMany('Acelle\Model\Campaign', 'customer_id')->where('status', '=', 'done')->orderBy('created_at', 'desc');
    }

    public function subscribers()
    {
        return $this->hasManyThrough('Acelle\Model\Subscriber', 'Acelle\Model\MailList', 'customer_id');
    }

    public function trackingLogs()
    {
        return $this->hasMany('Acelle\Model\TrackingLog', 'customer_id')->orderBy('created_at', 'asc');
    }

    public function automation2s()
    {
        return $this->hasMany('Acelle\Model\Automation2', 'customer_id');
    }

    public function activeAutomation2s()
    {
        return $this->hasMany('Acelle\Model\Automation2', 'customer_id')->where('status', Automation2::STATUS_ACTIVE);
    }

    public function sendingDomains()
    {
        return $this->hasMany('Acelle\Model\SendingDomain', 'customer_id');
    }

    // Only direct senders children
    public function senders()
    {
        return $this->hasMany('Acelle\Model\Sender', 'customer_id');
    }

    // tracking domain
    public function trackingDomains()
    {
        return $this->hasMany('Acelle\Model\TrackingDomain', 'customer_id');
    }

    public function products()
    {
        return $this->hasMany('Acelle\Model\Product', 'customer_id');
    }

    public function forms()
    {
        return $this->hasMany('Acelle\Model\Form', 'customer_id');
    }

    public function websites()
    {
        return $this->hasMany('Acelle\Model\Website', 'customer_id');
    }

    public function sources()
    {
        return $this->hasmany('Acelle\Model\Source', 'customer_id');
    }

    public function getVerifiedTrackingDomainOptions()
    {
        return $this->trackingDomains()->verified()->get()->map(function ($domain) {
            return ['value' => $domain->uid, 'text' => $domain->name];
        });
    }

    public function subscribersCount($cache = false)
    {
        if ($cache) {
            return $this->readCache('SubscriberCount');
        }

        // return distinctCount($this->subscribers(), 'subscribers.email', 'distinct');
        return $this->subscribers()->count();
    }

    public function subscribersUsage($cache = false)
    {
        $max = $this->maxSubscribers();
        $count = $this->subscribersCount($cache);

        if ($max == '∞') {
            return 0;
        }
        if ($max == 0) {
            return 0;
        }
        if ($count > $max) {
            return 100;
        }

        return round((($count / $max) * 100), 2);
    }

    /**
     * Calculate subscibers usage.
     *
     * @return number
     */
    public function displaySubscribersUsage()
    {
        if ($this->maxSubscribers() == '∞') {
            return trans('messages.unlimited');
        }

        // @todo: avoid using cached value in a function
        //        cache value must be called directly from view only
        return $this->readCache('SubscriberUsage', 0).'%';
    }

    public function listsCount()
    {
        return $this->lists()->count();
    }

    public function campaignsCount()
    {
        return $this->campaigns()->count();
    }

    public function newProductSource($type)
    {
        $class = '\\Acelle\\Model\\' . $type;
        $source = new $class();
        $source->customer_id = $this->id;
        $source->type = $type;

        return $source;
    }

    public function findProductSource($type)
    {
        $source = $this->sources()
            ->where('type', '=', $type)
            ->first();

        if (!$source) {
            $source = new Source();
            $source->customer_id = $this->id;
            $source->type = $type;
        }

        return $source;
    }

    public function getConnectedWebsiteSelectOptions($long = true)
    {
        $query = $this->websites()->connected();

        $result = $query->orderBy('title')->get()->map(function ($item) use ($long) {
            if ($long) {
                return ['value' => $item->uid, 'text' => '<span class="fw-600">' . $item->title . '</span><br><span class="text-muted">' . $item->url . '</span>'];
            } else {
                return ['value' => $item->uid, 'text' => $item->title];
            }
        });

        return $result;
    }

    public function getSubscriberCountByStatus($status)
    {
        // @note: in this particular case, a simple count(distinct) query is much more efficient
        $query = $this->subscribers()->where('subscribers.status', $status)->distinct('subscribers.email');

        return $query->count();
    }

    public function getBounceFeedbackRate()
    {
        $delivery = $this->trackingLogs()->count();

        if ($delivery == 0) {
            return 0;
        }

        $bounce = \DB::table('bounce_logs')->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'bounce_logs.message_id')->count();
        $feedback = \DB::table('feedback_logs')->leftJoin('tracking_logs', 'tracking_logs.message_id', '=', 'feedback_logs.message_id')->count();

        $percentage = ($feedback + $bounce) / $delivery;
    }

    public function sendingDomainsCount()
    {
        return $this->sendingDomains()->count();
    }

    public function automationsCount()
    {
        return $this->automation2sCount();
    }

    public function automation2sCount()
    {
        return $this->automation2s()->count();
    }

    public function newProduct()
    {
        $product = new \Acelle\Model\Product();
        $product->customer_id = $this->id;

        return $product;
    }

    public function newDefaultCampaign()
    {
        $campaign = Campaign::newDefault();
        $campaign->customer_id = $this->id;

        // default signature
        $defaultSignature = $this->signatures()->isDefault()->first();
        if ($defaultSignature) {
            $campaign->signature_id = $defaultSignature->id;
        }

        return $campaign;
    }

    public function createAbandonedEmailAutomation($store)
    {
        $auto = $this->automation2s()->create([
            'name' => 'Abandoned Cart Notification - Auto',
            'mail_list_id' => $store->getList()->id,
            'status' => 'inactive',
        ]);

        $email = new \Acelle\Model\Email([
            'action_id' => '1000000001',
        ]);
        $email->customer_id = $this->id;
        $email->automation2_id = $auto->id;
        $email->save();

        $auto->data = json_encode([
            [
                "title" => "Abandoned Cart Reminder",
                "id" => "trigger",
                "type" => "ElementTrigger",
                "child" => "1000000001",
                "options" => [
                    "key" => "woo-abandoned-cart",
                    "type" => "woo-abandoned-cart",
                    "source_uid" => $store->uid,
                    "wait" => "24_hour",
                    "init"  => "true"
                ]
            ], [
                "title" => "Hey, you have an item left in cart",
                "id" => "1000000001",
                "type" => "ElementAction",
                "child" => null,
                "options" => [
                    "init" => "true",
                    "email_uid" => $email->uid
                ]
            ]
        ]);

        $auto->save();

        return $auto;
    }

    public function getAbandonedEmailAutomation($store)
    {
        $auto = $this->automation2s()->where('mail_list_id', '=', $store->mail_list_id)->first();
        if (!$auto) {
            $auto = $this->createAbandonedEmailAutomation($store);
        }
        return $auto;
    }

    public function getSelectOptions($type = null)
    {
        $query = $this->sources();

        if ($type) {
            $query = $query->where('type', '=', $type);
        }

        return $query->get()->map(function ($source) {
            return ['text' => $source->getData()['data']['name'], 'value' => $source->uid];
        });
    }

    public static function subscribersCountByTime($begin, $end, $customer_id = null, $list_id = null, $status = null)
    {
        $query = \Acelle\Model\Subscriber::leftJoin('mail_lists', 'mail_lists.id', '=', 'subscribers.mail_list_id')
                                ->leftJoin('customers', 'customers.id', '=', 'mail_lists.customer_id');

        if (isset($list_id)) {
            $query = $query->where('subscribers.mail_list_id', '=', $list_id);
        }
        if (isset($customer_id)) {
            $query = $query->where('customers.id', '=', $customer_id);
        }
        if (isset($status)) {
            $query = $query->where('subscribers.status', '=', $status);
        }

        $query = $query->where('subscribers.created_at', '>=', $begin)
                        ->where('subscribers.created_at', '<=', $end);

        return $query->count();
    }

    public function getMailListSelectOptions($options = [], $cache = false)
    {
        $query = $this->mailLists();

        # Other list
        if (isset($options['other_list_of'])) {
            $query->where('id', '!=', $options['other_list_of']);
        }

        $result = $query->orderBy('name')->get()->map(function ($item) use ($cache) {
            return ['id' => $item->id, 'value' => $item->uid, 'text' => $item->name.' ('.$item->subscribersCount($cache).' '.strtolower(trans('messages.subscribers')).')'];
        });

        return $result;
    }

    /**
     * Update Campaign cached data.
     */
    public function getCacheIndex()
    {
        // cache indexes
        return [
            // @note: SubscriberCount must come first as its value shall be used by the others
            'SubscriberCount' => function () {
                return $this->subscribersCount(false);
            },
            'SubscriberUsage' => function () {
                return $this->subscribersUsage(true);
            },
            'SubscribedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_SUBSCRIBED);
            },
            'UnsubscribedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_UNSUBSCRIBED);
            },
            'UnconfirmedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_UNCONFIRMED);
            },
            'BlacklistedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_BLACKLISTED);
            },
            'SpamReportedCount' => function () {
                return $this->getSubscriberCountByStatus(Subscriber::STATUS_SPAM_REPORTED);
            },
            'MailListSelectOptions' => function () {
                return $this->getMailListSelectOptions([], true);
            },
            'Bounce/FeedbackRate' => function () {
                return $this->getBounceFeedbackRate();
            },
        ];
    }

    public function master()
    {
        return MasterCustomer::on('mysql')->where('uid', $this->uid)->first();
    }

    public static function sync(MasterCustomer $masterCustomer)
    {

        if (!$masterCustomer->hasLocalDb()) {
            throw new \Exception("Master customer #{$masterCustomer->id} '{$masterCustomer->name}' does not have a local db configured in 'db_connection'");
        }

        $localCustomer = static::on($masterCustomer->db_connection)->find($masterCustomer->id);

        if (!is_null($localCustomer)) {
            throw new \Exception("Master customer #{$masterCustomer->id} '{$masterCustomer->name}' already has a local instance at '{$masterCustomer->db_connection}'");
        }

        $localCustomer = new static();
        $localCustomer->id = $masterCustomer->id;
        $localCustomer->uid = $masterCustomer->uid;

        return $localCustomer->setConnection($masterCustomer->db_connection)->save();
    }

    public function newSignature()
    {
        $signature = Signature::newDefault();
        $signature->customer_id = $this->id;

        return $signature;
    }

    public function newDefaultAutomation2()
    {
        $automation = $this->automation2s()->make()->newDefault();
        $automation->customer_id = $this->id;

        return $automation;
    }

    public function sites()
    {
        return $this->hasMany(Site::class, 'customer_id');
    }

    public function newSite()
    {
        $site = $this->sites()->make()->newDefault();
        $site->customer_id = $this->id;

        return $site;
    }
}
