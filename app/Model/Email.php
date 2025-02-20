<?php

namespace Acelle\Model;

use Illuminate\Database\Eloquent\Model;
use Validator;
use ZipArchive;
use KubAT\PhpSimple\HtmlDomParser;
use Acelle\Model\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Acelle\Jobs\SendSingleMessage;
use Acelle\Library\Traits\HasTemplate;
use Acelle\Library\Traits\HasUid;
use Acelle\Library\Contracts\HasTemplateInterface;
use Acelle\Library\HtmlHandler\InjectTrackingPixel;
use Acelle\Library\HtmlHandler\TransformUrl;
use Acelle\Library\RouletteWheel;
use Closure;
use Exception;

class Email extends Model implements HasTemplateInterface
{
    use HasTemplate;
    use HasUid;

    // Email types
    public const TYPE_REGULAR = 'regular';
    public const TYPE_PLAIN_TEXT = 'plain-text';

    protected $serversPool = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'subject', 'from_email', 'from_name', 'reply_to', 'sign_dkim', 'track_open', 'track_click', 'action_id', 'skip_failed_message',
        'use_default_sending_server_from_email',
    ];

    // Cached HTML content
    protected $parsedContent = null;

    /**
     * Association with mailList through mail_list_id column.
     */
    public function automation()
    {
        return $this->belongsTo('Acelle\Model\Automation2', 'automation2_id');
    }

    /**
     * Get the customer.
     */
    public function customer()
    {
        return $this->belongsTo('Acelle\Model\Customer');
    }

    /**
     * Association with attachments.
     */
    public function attachments()
    {
        return $this->hasMany('Acelle\Model\Attachment');
    }

    /**
     * Association with email links.
     */
    public function emailLinks()
    {
        return $this->hasMany('Acelle\Model\EmailLink');
    }

    /**
     * Association with open logs.
     */
    public function trackingLogs()
    {
        return $this->hasMany('Acelle\Model\TrackingLog');
    }

    public function emailWebhooks()
    {
        return $this->hasMany('Acelle\Model\EmailWebhook');
    }

    /**
     * Get email's associated tracking domain.
     */
    public function trackingDomain()
    {
        return $this->belongsTo('Acelle\Model\TrackingDomain', 'tracking_domain_id');
    }

    public function signature()
    {
        return $this->belongsTo('Acelle\Model\Signature', 'signature_id');
    }

    public function setSignature($signature)
    {
        $this->signature_id = $signature ? $signature->id : null;
        $this->save();
    }

    /**
     * Get email's default mail list.
     */
    public function defaultMailList()
    {
        return $this->automation->mailList();
    }

    /**
     * Create automation rules.
     *
     * @return array
     */
    public function rules($request = null)
    {
        $rules = [
            'subject' => 'required',
            'from_email' => 'required|email',
            'from_name' => 'required',
        ];

        if ($this->use_default_sending_server_from_email) {
            $rules['from_email'] = 'nullable|email';
            $rules['reply_to'] = 'nullable|email';
        } else {
            $rules['from_email'] = 'required|email';
            $rules['reply_to'] = 'required|email';
        }

        // tracking domain
        if (isset($request) && $request->custom_tracking_domain) {
            $rules['tracking_domain_uid'] = 'required';
        }

        return $rules;
    }

    /**
     * Upload attachment.
     */
    public function uploadAttachment($file)
    {
        $file_name = $file->getClientOriginalName();
        $att = $this->attachments()->make();
        $att->size = $file->getSize();
        $att->name = $file->getClientOriginalName();

        $path = $file->move(
            $this->getAttachmentPath(),
            $att->name
        );

        $att->file = $this->getAttachmentPath($att->name);
        $att->save();

        return $att;
    }

    /**
     * Get attachment path.
     */
    public function getAttachmentPath($path = null)
    {
        return $this->customer->getAttachmentsPath($path);
    }

    /**
     * Find and update email links.
     */
    public function updateLinks()
    {
        if (!$this->getTemplateContent()) {
            return false;
        }

        $links = [];

        // find all links from contents
        // Fix: str_get_html returning false
        defined('MAX_FILE_SIZE') || define('MAX_FILE_SIZE', 10000000);
        $document = HtmlDomParser::str_get_html($this->getTemplateContent());
        foreach ($document->find('a') as $element) {
            if (preg_match('/^http/', $element->href) != 0) {
                $links[] = trim($element->href);
            }
        }

        // delete al bold links
        $this->emailLinks()->whereNotIn('link', $links)->delete();

        foreach ($links as $link) {
            $exist = $this->emailLinks()->where('link', '=', $link)->count();

            if (!$exist) {
                $emailLink = $this->emailLinks()->make([
                    'link' => $link,
                ]);

                $emailLink->customer_id = $this->customer_id;
                $emailLink->save();
            }
        }
    }

    public function getServersPool()
    {
        if (is_null($this->serversPool)) {
            // Available sending servers
            $serversAndWeights = $this->automation->mailList->getSendingServers();
            // No sending server ready for delivery
            if (empty($serversAndWeights)) {
                throw new Exception('No sending server avaialble');
            }

            $pool = new RouletteWheel();
            foreach ($serversAndWeights as $serverId => $weight) {
                $server = SendingServer::find($serverId)->mapType();
                $pool->add($server, $weight);
            }

            $this->serversPool = $pool;
        }

        return $this->serversPool;
    }

    public function queueDeliverTo($subscriber, $autoTriggerId, $actionId, $queue)
    {
        $serversPool = $this->getServersPool();

        if (config('app.saas')) {
            $subscription = $this->automation->customer->getCurrentActiveGeneralSubscription();
        } else {
            $subscription = null;
        }

        if (is_null($subscription)) {
            throw new Exception("Customer {$this->customer->name} has no active subscription, quit sending");
        }

        $job = new SendSingleMessage(
            $this,
            $subscriber,
            $serversPool,
            $subscription,
            $autoTriggerId,
            $actionId,
        );

        $connection = config('custom.automation_queue_connection');

        if (!is_null($this->customer->custom_queue_name)) {
            $queue = $this->customer->custom_queue_name;
        } elseif (is_null($queue)) {
            $queue = ACM_QUEUE_TYPE_SINGLE;
            // $queue is currently determined by Automation/Send action
            // in which priority is determined by trigger type
        } else {
            // use the $queue passed to
        }

        if ($connection) {
            // By default, this value is null, meaning that automation should use the default queue connection
            // specified in the .env file (QUEUE_CONNECTION)
            $job->onConnection($connection)->onQueue($queue);
        } else {
            $job->onQueue($queue);
        }

        // @important: the original method dispatch() sometimes returns PendingDispatch object instead of the dispatched JobID
        // So, here we should use safe_dispatch instead
        $jobId = safe_dispatch($job);

        return $jobId;
    }

    public function sendTestEmail($emailAddress)
    {
        $validator = Validator::make([ 'email' => $emailAddress ], [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            $firstErrorMsg = $validator->errors()->first();
            throw new Exception($firstErrorMsg);
        }

        $server = $this->automation->mailList->pickSendingServer();

        // Build a valid message with a fake contact
        // build a temporary subscriber oject used to pass through the sending methods
        $subscriber = $this->createStdClassSubscriber(['email' => $emailAddress]);
        list($message, $msgId) = $this->prepareEmail($subscriber, $server);
        $server->send($message);
    }

    /**
     * Log delivery message, used for later tracking.
     */
    public function trackMessage($response, $subscriber, $server, $msgId, $triggerId = null)
    {
        // @todo: customerneedcheck
        $params = array_merge(array(
            'email_id' => $this->id,
            'message_id' => $msgId,
            'subscriber_id' => $subscriber->id,
            'sending_server_id' => $server->id,
            'customer_id' => $this->automation->customer->id,
            'auto_trigger_id' => $triggerId,
        ), $response);

        if (!isset($params['runtime_message_id'])) {
            $params['runtime_message_id'] = $msgId;
        }

        // create tracking log for message
        $this->trackingLogs()->create($params);
    }

    public function isOpened($subscriber)
    {
        if (is_null($subscriber)) {
            return false;
        }

        return $this->trackingLogs()->where('subscriber_id', $subscriber->id)
                            ->join('open_logs', 'open_logs.message_id', '=', 'tracking_logs.message_id')->exists();
    }

    public function isClicked($subscriber)
    {
        if (is_null($subscriber)) {
            return false;
        }

        return $this->trackingLogs()->where('subscriber_id', $subscriber->id)
                            ->join('click_logs', 'click_logs.message_id', '=', 'tracking_logs.message_id')->exists();
    }

    /**
     * Fill email's fields from request.
     */
    public function fillAttributes($params)
    {
        $this->fill($params);

        // Tacking domain
        if (isset($params['custom_tracking_domain']) && $params['custom_tracking_domain'] && isset($params['tracking_domain_uid'])) {
            $tracking_domain = \Acelle\Model\TrackingDomain::findByUid($params['tracking_domain_uid']);
            if ($tracking_domain) {
                $this->tracking_domain_id = $tracking_domain->id;
            } else {
                $this->tracking_domain_id = null;
            }
        } else {
            $this->tracking_domain_id = null;
        }
    }

    public function isSetup()
    {
        return $this->subject && $this->reply_to && $this->from_email && $this->template;
    }

    public function deleteAndCleanup()
    {
        if ($this->template) {
            $this->template->deleteAndCleanup();
        }

        $this->delete();
    }

    public function logger()
    {
        return $this->automation->logger();
    }

    public function newWebhook()
    {
        $webhook = new \Acelle\Model\EmailWebhook();
        $webhook->email_id = $this->id;

        return $webhook;
    }

    public function isStageExcluded(string $name): bool
    {
        switch ($name) {
            case InjectTrackingPixel::class:
                if ($this->track_open) {
                    return false;
                } else {
                    return true;
                }
                break;

            case TransformUrl::class:
                if ($this->track_click) {
                    return false;
                } else {
                    return true;
                }
                break;

            default:
                // do not exclude by default
                return false;
                break;
        }
    }

    public function copy()
    {
        $copy = $this->replicate();
        $copy->generateUid();
        $copy->template_id = null;
        $copy->save();

        // Notice that ->template is still available even if template_id is set to null
        if ($this->template) {
            $copy->setTemplate($this->template);
        }

        return $copy;
    }

    public static function newDefault()
    {
        $email = new self([
            'sign_dkim' => true,
            'track_open' => true,
            'track_click' => true,
        ]);

        $email->skip_failed_message = Setting::isYes('email.default.skip_failed_message');

        return $email;
    }

    public function setPreheader($preheader)
    {
        $this->preheader = $preheader;
        $this->save();
    }

    public function removePreheader()
    {
        $this->preheader = null;
        $this->save();
    }

    public function checkDelayFlag()
    {
        return false; // not supported
    }

    public function debug(Closure $callback = null)
    {
        // @important: temporary function to avoid Automation bug
    }

    public function useSendingServerDefaultFromEmailAddress()
    {
        return $this->use_default_sending_server_from_email == true;
    }
}
