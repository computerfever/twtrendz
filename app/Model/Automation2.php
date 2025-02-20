<?php

/**
 * Automation class.
 *
 * Model for automations
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
use DB;
use Acelle\Library\Automation\Action;
use Acelle\Library\Automation\Trigger;
use Acelle\Library\Automation\Send;
use Acelle\Library\Automation\Wait;
use Acelle\Library\Automation\Evaluate;
use Acelle\Library\Automation\Operate;
use Acelle\Library\Automation\Webhook;
use Carbon\Carbon;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Acelle\Library\Lockable;
use Acelle\Model\Setting;
use Acelle\Model\MailList;
use Acelle\Model\Email;
use DateTime;
use DateTimeZone;
use Exception;
use Throwable;
use Acelle\Library\Traits\HasUid;
use Acelle\Library\Traits\HasCache;

class Automation2 extends Model
{
    use HasUid;
    use HasCache;

    // Automation status
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    public const TRIGGER_TYPE_WELCOME_NEW_SUBSCRIBER = 'welcome-new-subscriber';
    public const TRIGGER_TYPE_SAY_GOODBYE_TO_SUBSCRIBER = 'say-goodbye-subscriber';
    public const TRIGGER_TYPE_SAY_HAPPY_BIRTHDAY = 'say-happy-birthday';
    public const TRIGGER_SUBSCRIPTION_ANNIVERSARY_DATE = 'subscriber-added-date'; // in progress
    public const TRIGGER_PARTICULAR_DATE = 'specific-date'; // in progress
    public const TRIGGER_API = 'api-3-0';
    public const TRIGGER_WEEKLY_RECURRING = 'weekly-recurring';
    public const TRIGGER_MONTHLY_RECURRING = 'monthly-recurring';
    public const TRIGGER_TAG_BASED = 'tag-based';
    public const TRIGGER_WOO_ABANDONED_CART = 'woo-abandoned-cart';
    public const TRIGGER_REMOVE_TAG = 'remove-tag';
    public const TRIGGER_ATTRIBUTE_UPDATE = 'attribute-update';

    /**
     * Items per page.
     *
     * @var array
     */
    public const ITEMS_PER_PAGE = 25;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uid', 'name', 'mail_list_id', 'status', 'data'
    ];

    /**
     * Association with mailList through mail_list_id column.
     */
    public function mailList()
    {
        return $this->belongsTo('Acelle\Model\MailList');
    }

    /**
     * Get all of the links for the automation.
     */
    public function emailLinks()
    {
        return $this->hasManyThrough('Acelle\Model\EmailLink', 'Acelle\Model\Email');
    }

    public function triggerSessions()
    {
        return $this->hasMany('Acelle\Model\TriggerSession');
    }

    /**
     * Get all of the emails for the automation.
     */
    public function emails()
    {
        return $this->hasMany('Acelle\Model\Email');
    }

    /**
     * Association.
     */
    public function autoTriggers()
    {
        return $this->hasMany('Acelle\Model\AutoTrigger');
    }

    public function subscribersNotTriggeredThisYear()
    {
        $thisYear = $this->customer->getCurrentTime()->format('Y');
        $thisId = $this->id;
        return $this->subscribersToSend()->whereNotIn('subscribers.id', function ($query) use ($thisYear) {
            $query->select('subscriber_id')
                  ->from('auto_triggers')
                  ->where('automation2_id', $this->id)
                  ->whereRaw(sprintf('year(%s) = %s', table('auto_triggers.created_at'), $thisYear));
        });
    }

    /**
     * Association.
     */
    public function segment()
    {
        return $this->belongsTo('Acelle\Model\Segment');
    }

    /**
     * Association: triggers that have not finished
     */
    public function pendingAutoTriggers()
    {
        $leaves = $this->getLeafActions();
        $condition = '('.implode(' AND ', array_map(function ($e) {
            return "executed_index NOT LIKE '%{$e}'";
        }, $leaves)).')';
        $query = $this->autoTriggers()->whereRaw($condition);
        return $query;
    }

    /**
     * Association.
     */
    public function customer()
    {
        return $this->belongsTo('Acelle\Model\Customer');
    }

    /**
     * Association.
     */
    public function timelines()
    {
        return $this->hasMany('Acelle\Model\Timeline')->orderBy('created_at', 'DESC');
    }

    /**
     * Create automation rules.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'mail_list_uid' => 'required',
        ];
    }

    public function getElementById($id)
    {
        $data = $this->getElements()[$id];

        // search by id

        return $element;
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public function scopeSearch($query, $keyword)
    {
        // Keyword
        if (!empty(trim($keyword))) {
            $query = $query->where('name', 'like', '%'.trim($keyword).'%');
        }

        return $query;
    }

    /**
     * enable automation.
     */
    public function enable()
    {
        // @IMPORTANT: need more validation like this
        if (empty($this->getTriggerType())) {
            throw new \Exception("Automation trigger not set up");
        }

        $this->status = self::STATUS_ACTIVE;
        $this->save();
    }

    /**
     * disable automation.
     */
    public function disable()
    {
        $this->status = self::STATUS_INACTIVE;
        $this->save();
    }

    public function saveDataAndResetTriggers($data)
    {
        DB::transaction(function () use ($data) {
            $this->resetListRelatedData();
            $this->saveData($data);
        });
    }

    /**
     * disable automation.
     */
    public function saveData($data)
    {
        $this->data = $this->validateData($data);
        $this->save();
    }

    public function retryAllFailedActions()
    {
        $connection = config('custom.automation_queue_connection');
        $queue = ACM_QUEUE_TYPE_HIGH;

        $job = new \Acelle\Jobs\RetryAllFailedActions($this);

        if ($connection) {
            // By default, this value is null, meaning that automation should use the default queue connection
            // specified in the .env file (QUEUE_CONNECTION)
            $job->onConnection($connection)->onQueue($queue);
        } else {
            $job->onQueue($queue);
        }

        $jobId = dispatch($job);

        return $jobId;
    }

    public function validateData($data)
    {
        // Check Element Action => if email UID is no longer valid => remove Action
        $entries = json_decode($data, true);
        $newData = [];

        foreach ($entries as $entry) {
            $action = $this->getAction($entry);

            // @TODO should be transparent in Action element
            if ($action->getType() == 'ElementAction') {
                if ($action->getOption('init') == "true") { // @IMPORTANT VERY IMPORTANT! Use "true", it is because even "false" == true!!!!
                    if (!$this->emails()->where('uid', $action->getOption('email_uid'))->exists()) {
                        $action->fixInvalidEmailUid();
                    }
                }
            }
            $newData[] = $action->toJson();
        }
        return json_encode($newData);
    }

    /**
     * Transform automation JSON to Javascript syntax
     */
    public function getData()
    {
        return isset($this->data) ? preg_replace('/"([^"]+)"\s*:\s*/', '$1:', $this->data) : '[]';
    }

    /**
     * get all tree elements.
     */
    public function getElements($hash = false) # true => return hash, false (default) => return stdObject
    {
        return isset($this->data) && !empty($this->data) ? json_decode($this->data, $hash) : [];
    }

    /**
     * get trigger.
     */
    public function getTrigger()
    {
        $elements = $this->getElements();

        return empty($elements) ? new AutomationElement(null) : new AutomationElement($elements[0]);
    }

    /**
     * get element by id.
     */
    public function getElement($id = null)
    {
        $elements = $this->getElements();

        foreach ($elements as $element) {
            if ($element->id == $id) {
                return new AutomationElement($element);
            }
        }

        return new AutomationElement(null);
    }

    /**
     * Get started time.
     */
    public function getStartedTime()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get delay options.
     */
    public static function getDelayOptions($moreOptions = [])
    {
        return array_merge($moreOptions, [
            ['text' => trans_choice('messages.automation.delay.minute', 1), 'value' => '1 minute'],
            ['text' => trans_choice('messages.automation.delay.minute', 30), 'value' => '30 minutes'],
            ['text' => trans_choice('messages.automation.delay.hour', 1), 'value' => '1 hours'],
            ['text' => trans_choice('messages.automation.delay.hour', 2), 'value' => '2 hours'],
            ['text' => trans_choice('messages.automation.delay.hour', 4), 'value' => '4 hours'],
            ['text' => trans_choice('messages.automation.delay.hour', 8), 'value' => '8 hours'],
            ['text' => trans_choice('messages.automation.delay.hour', 12), 'value' => '12 hours'],

            ['text' => trans_choice('messages.automation.delay.day', 1), 'value' => '1 day'],
            ['text' => trans_choice('messages.automation.delay.day', 2), 'value' => '2 days'],
            ['text' => trans_choice('messages.automation.delay.day', 3), 'value' => '3 days'],
            ['text' => trans_choice('messages.automation.delay.day', 4), 'value' => '4 days'],
            ['text' => trans_choice('messages.automation.delay.day', 5), 'value' => '5 days'],
            ['text' => trans_choice('messages.automation.delay.day', 6), 'value' => '6 days'],
            ['text' => trans_choice('messages.automation.delay.week', 1), 'value' => '1 week'],
            ['text' => trans_choice('messages.automation.delay.week', 2), 'value' => '2 weeks'],
            ['text' => trans_choice('messages.automation.delay.month', 1), 'value' => '1 month'],
            ['text' => trans_choice('messages.automation.delay.month', 2), 'value' => '2 months'],

            ['text' => trans('messages.automation.delay.custom'), 'value' => 'custom'],
        ]);
    }

    /**
     * Get delay or before options.
     */
    public static function getDelayBeforeOptions()
    {
        return [
            ['text' => trans_choice('messages.automation.delay.0_day', 0), 'value' => '0 day'],
            ['text' => trans_choice('messages.automation.delay.day', 1), 'value' => '1 day'],
            ['text' => trans_choice('messages.automation.delay.day', 2), 'value' => '2 days'],
            ['text' => trans_choice('messages.automation.delay.day', 3), 'value' => '3 days'],
            ['text' => trans_choice('messages.automation.delay.day', 4), 'value' => '4 days'],
            ['text' => trans_choice('messages.automation.delay.day', 5), 'value' => '5 days'],
            ['text' => trans_choice('messages.automation.delay.day', 6), 'value' => '6 days'],
            ['text' => trans_choice('messages.automation.delay.week', 1), 'value' => '1 week'],
            ['text' => trans_choice('messages.automation.delay.week', 2), 'value' => '2 weeks'],
            ['text' => trans_choice('messages.automation.delay.month', 1), 'value' => '1 month'],
            ['text' => trans_choice('messages.automation.delay.month', 2), 'value' => '2 months'],
        ];
    }

    /**
     * Get email links options.
     */
    public function getEmailLinkOptions()
    {
        $data = [];

        foreach ($this->emailLinks as $link) {
            $data[] = ['text' => $link->link, 'value' => $link->uid];
        }

        return $data;
    }

    /**
     * Get emails options.
     */
    public function getEmailOptions()
    {
        $data = [];

        foreach ($this->emails as $email) {
            $data[] = ['text' => $email->subject, 'value' => $email->uid];
        }

        return $data;
    }

    /**
     * Initiate a trigger with a given subscriber.
     */
    public function initTrigger($subscriber, $force = false, $session = null)
    {
        if (!$subscriber->isSubscribed() && $force == false) {
            $this->logger()->warning(sprintf('Cannot trigger contact %s, current status is: %s', $subscriber->email, $subscriber->status));
            return;
        }

        $trigger = null;
        $sessionId = is_null($session) ? null : $session->id;
        DB::transaction(function () use (&$trigger, $subscriber, $sessionId) {
            $trigger = new AutoTrigger();
            $trigger->customer_id = $this->customer_id;
            $trigger->trigger_session_id = $sessionId;
            $trigger->subscriber()->associate($subscriber); // assign to subscriber_id field
            $this->autoTriggers()->save($trigger);
            $trigger->updateWorkflow();
            $trigger->cacheSubscriberInfo();

            $this->logger()->info(sprintf('@TRIGGER (%s) for %s', $this->getTriggerType(), $subscriber->email));
        });

        return $trigger;
    }

    /**
     * Scan for triggers updates and new triggers.
     */
    public static function run()
    {
        $customers = Customer::all();
        foreach ($customers as $customer) {

            // IMPORTANT!!!
            $customer->setUserDbConnection();

            // Only one automation process to run at one given time
            $automations = $customer->local()->activeAutomation2s;
            foreach ($automations as $automation) {
                $automationLockFile = $customer->getLockPath('automation-lock-'.$automation->uid);
                $lock = new Lockable($automationLockFile);
                $timeout = 5; // seconds
                $timeoutCallback = function () use ($automation) {
                    // pass this ot the getExclusiveLock method
                    // to have it silently quit, without throwing an exception
                    $automation->logger()->info(sprintf('LOCK timeout, another process is currently handling automation "%s"', $automation->name));
                    return;
                };

                $lock->getExclusiveLock(function ($f) use ($customer, $automation) {
                    if (!config('app.saas') || $customer->getCurrentActiveGeneralSubscription()) {
                        $automation->logger()->info(sprintf('Checking automation "%s"', $automation->name));
                        $automation->check();
                    } else {
                        $automation->logger()->warning(sprintf('Automation "%s" skipped, user "%s" not on active subscription', $automation->name, $customer->displayName()));
                    }
                }, $timeout, $timeoutCallback);
            }
        }
    }

    // Automation::check() ==> AutoTrigger::check() ==> Action::execute()
    public function check()
    {
        // Only via the check() method, errors are logged to automation.error
        try {
            $this->checkForNewTriggers();
            $this->checkForExistingTriggersUpdate();
            $this->updateCache();
            $this->setLastError(null);
        } catch (Throwable $ex) {
            $this->setLastError($ex->getMessage());
            $this->logger()->warning(sprintf('Error while executing automation "%s". %s', $this->name, $ex->getMessage()));
        } finally {
            // Update the updated_at field
            // $this->touch();
        }
    }

    /**
     * Check for new triggers.
     */
    public function checkForNewTriggers()
    {
        $this->logger()->info(sprintf('NEW > Start checking for new trigger'));

        $triggerName = $this->getTriggerAction()->getOption('key');

        // @TODO workaround for automations that are enabled (!) but without a trigger
        if (empty($triggerName)) {
            return;
        }

        // @TODO: move to trigger object
        switch ($triggerName) {
            case self::TRIGGER_TYPE_WELCOME_NEW_SUBSCRIBER:
                /* Triggered immediately by MailListSubscription event
                 * See TriggerAutomation listener
                 *
                 */
                break;
            case self::TRIGGER_PARTICULAR_DATE:
                $this->checkForSpecificDatetime();
                break;
            case self::TRIGGER_TYPE_SAY_GOODBYE_TO_SUBSCRIBER:
                /* triggered immediately by MailListSubscription event */
                break;
            case self::TRIGGER_TYPE_SAY_HAPPY_BIRTHDAY:
                $this->checkForDateOfBirth();
                break;
            case self::TRIGGER_API:
                // Just wait for API call
                break;
            case self::TRIGGER_WEEKLY_RECURRING:
                $this->checkForWeeklyRecurring();
                break;
            case self::TRIGGER_MONTHLY_RECURRING:
                $this->checkForMonthlyRecurring();
                break;
            case self::TRIGGER_SUBSCRIPTION_ANNIVERSARY_DATE:
                // In progress
                break;
            case self::TRIGGER_WOO_ABANDONED_CART:
                $this->checkForAbandonedCart();
                // no break
            case self::TRIGGER_TAG_BASED:
                break;
            case self::TRIGGER_REMOVE_TAG:
                break;
            case self::TRIGGER_ATTRIBUTE_UPDATE:
                break;
            case 'others':
                // others
                break;
            default:
                throw new \Exception(sprintf('[Automation: `%s`, Customer account: `%s`] Unknown Automation trigger type: %s', $this->name, $this->customer->uid, $triggerName));
        }
        $this->updateCache();
        $this->logger()->info(sprintf('NEW > Finish checking for new trigger'));
    }

    public function checkForWeeklyRecurring()
    {
        $selectedWeekDays = $this->getTriggerAction()->getOptions()['days_of_week'];
        $today = Carbon::now($this->customer->timezone);
        if (!in_array($today->dayOfWeek, $selectedWeekDays)) {
            $this->logger()->info(sprintf('Not the right week day: CURRENT %s (%s) != %s', $today->dayOfWeek, $today->toString(), implode(' ', $selectedWeekDays)));
            return;
        }

        // TODAY + CURRENT TIME
        $currentTime = new DateTime('now', new DateTimeZone($this->customer->timezone));
        // TODAY + GIVEN TIME
        $triggerTime = new DateTime($this->getTriggerAction()->getOptions()['at'], new DateTimeZone($this->customer->timezone));

        if ($currentTime < $triggerTime) {
            $this->logger()->info(sprintf('Not the right time: CURRENT %s < %s, wait for %s minutes', $currentTime->format('H:i:s'), $triggerTime->format('H:i:s'), (new Carbon($triggerTime))->diffInMinutes($currentTime)));
            return;
        }

        // Only trigger once a day
        $startOfDayUtc = $today->copy()->startOfDay()->timezone(config('app.timezone'));
        $endOfDayUtc = $today->copy()->endOfDay()->timezone(config('app.timezone'));

        $session = $this->triggerSessions()->where('created_at', '>=', $startOfDayUtc)->where('created_at', '<=', $endOfDayUtc)->first();

        if ($session) {
            $this->logger()->info(sprintf('A session already exists: TriggerSession#%s, triggered same day %s at %s', $session->id, $session->created_at->diffForHumans(), $this->customer->formatDateTime($session->created_at, 'datetime_short')));
            return;
        }

        // Delete old sessions' triggers
        $this->triggerSessions()->delete();
        $this->autoTriggers()->delete(); // Delete legacy triggers that were not bound to a session

        // Create new sessions
        $session = $this->createTriggerSession();

        $this->forceTriggerAll($session);
    }

    public function createTriggerSession()
    {
        $session = new TriggerSession();
        $session->customer_id = $this->customer_id;
        return $this->triggerSessions()->save($session);
    }

    public function checkForMonthlyRecurring()
    {
        $thisId = $this->id;
        // TODAY + CURRENT TIME
        $currentTime = new DateTime('now', new DateTimeZone($this->customer->timezone));
        // TODAY + GIVEN TIME
        $triggerTime = new DateTime($this->getTriggerAction()->getOptions()['at'], new DateTimeZone($this->customer->timezone));

        if ($currentTime < $triggerTime) {
            $this->logger()->info(sprintf('Not the right time: CURRENT %s < %s (%s), wait for %s minutes', $currentTime->format('H:i:s'), $triggerTime->format('H:i:s'), $this->customer->timezone, (new Carbon($triggerTime))->diffInMinutes($currentTime)));
            return;
        }

        $selectedDays = $this->getTriggerAction()->getOptions()['days_of_month'];

        $today = Carbon::now($this->customer->timezone);

        if (!in_array($today->day, $selectedDays)) {
            $this->logger()->info(sprintf('Not the right day: CURRENT %s (%s) != %s', $today->day, $today->toString(), implode(' ', $selectedDays)));
            return;
        }

        // Only trigger once a day
        // Counting by user timezone
        $startOfDayUtc = $today->startOfDay()->timezone(config('app.timezone'));

        $subscribers = $this->subscribersToSend()->leftJoin('auto_triggers', function ($join) use ($thisId, $startOfDayUtc) {
            $join->on('auto_triggers.subscriber_id', 'subscribers.id');
            $join->where('auto_triggers.automation2_id', $thisId);
            $join->where('auto_triggers.created_at', '>=', $startOfDayUtc);
        })
        ->whereNull('auto_triggers.subscriber_id')->get();

        // init trigger
        foreach ($subscribers as $subscriber) {
            $this->initTrigger($subscriber);
            $this->logger()->info(sprintf('NEW > ??? > Monthly recurring for %s, at %s', $subscriber->email, $startOfDayUtc->toString()));
        }
    }

    public function getSubscribersWithDateOfBirth()
    {
        $formatSql = config('custom.date_format_sql');
        $formatCarbon  = 'Y-m-d'; // DO NOT READ FROM config(), as it is used here only
        // TODAY + CURRENT TIME
        $currentTime = new DateTime('now', new DateTimeZone($this->customer->timezone));
        // TODAY + GIVEN TIME
        $triggerTime = new DateTime($this->getTriggerAction()->getOptions()['at'], new DateTimeZone($this->customer->timezone));

        // Get the modify interval: 1 days, 2 days... for example
        $interval = $this->getTriggerAction()->getOptions()['before'];
        $thisId = $this->id;

        $today = Carbon::now($this->customer->timezone)->modify($interval);
        $todayStr = $today->format($formatCarbon);
        $dobFieldUid = $this->getTriggerAction()->getOptions()['field'];
        $dobField = Field::findByUid($dobFieldUid)->custom_field_name;
        $query = $this->subscribers()
            ->addSelect('auto_triggers.created_at AS trigger_at')
            ->addSelect('auto_triggers.id AS auto_trigger_id')
            ->addSelect(
                // Format date-of-birth to somethine like "April-9" (%b-%e)
                DB::raw(sprintf("DATE_FORMAT(STR_TO_DATE(%s, '%s'), '%s') as dob", "subscribers.{$dobField}", config('custom.date_format_sql'), '%b-%e'))
            )
            ->addSelect(
                DB::raw(
                    // Make it the same year 2024 to count date diff
                    "DATEDIFF( DATE_FORMAT(STR_TO_DATE(subscribers.{$dobField}, '".config('custom.date_format_sql')."'), '2024-%m-%d') , DATE_FORMAT(NOW(), '2024-%m-%d')) AS datediff"
                )
            )
            ->leftJoin('auto_triggers', function ($join) use ($thisId) {
                $join->on('auto_triggers.subscriber_id', 'subscribers.id');
                $join->where('auto_triggers.automation2_id', $thisId);
            });

        return $query;
    }

    public function checkForDateOfBirth()
    {
        // TODAY + CURRENT TIME
        $currentTime = new DateTime('now', new DateTimeZone($this->customer->timezone));
        // TODAY + GIVEN TIME
        $triggerTime = new DateTime($this->getTriggerAction()->getOptions()['at'], new DateTimeZone($this->customer->timezone));

        if ($currentTime < $triggerTime) {
            $this->logger()->info(sprintf('Not the right time: CURRENT %s < %s', $currentTime->format('Y-m-d H:i:s'), $triggerTime->format('Y-m-d H:i:s')));
            return;
        }

        // Get the modify interval: 1 days, 2 days... for example
        $interval = $this->getTriggerAction()->getOptions()['before'];
        $thisId = $this->id;

        $today = Carbon::now($this->customer->timezone)->modify($interval);
        $dobFieldUid = $this->getTriggerAction()->getOptions()['field'];
        $dobField = Field::findByUid($dobFieldUid)->custom_field_name;

        // TODO: convert to user's timezone first!!!
        $subscribers = $this->subscribersNotTriggeredThisYear()
            ->whereIn(
                DB::raw("DATE_FORMAT(STR_TO_DATE(subscribers.{$dobField}, '".config('custom.date_format_sql')."'), '%m-%d')"),
                [$today->format('m-d')]
            )->get();

        // init trigger
        foreach ($subscribers as $subscriber) {
            $this->initTrigger($subscriber);
            $this->logger()->info(sprintf('NEW > ??? > Say happy birthday (%s) to %s', $interval, $subscriber->email));
        }
    }

    /**
     * Check for existing triggers update.
     */
    public function checkForExistingTriggersUpdate()
    {
        $this->logger()->info(sprintf('UPDATE > Start checking for trigger update'));

        /* FORCE RECHECK
        foreach ($this->autoTriggers as $trigger) {
            $trigger->check();
        }*/

        $pageCount = 0;
        paginate_query($this->pendingAutoTriggers(), $perPage = 500, $orderBy = 'auto_triggers.id', function ($pageNumber, $autoTriggers) use (&$pageCount) {
            $pageCount += 1;

            $this->logger()->info(sprintf("Checking page {$pageCount}"));
            foreach ($autoTriggers->get() as $trigger) {
                $this->logger()->info("Checking trigger ID #{$trigger->id}");
                $trigger->check();
            }
        });

        $this->logger()->info(sprintf('Finish checking for trigger update'));
    }

    /**
     * Check for list-subscription events.
     */
    public function triggerImportedContacts($importBatchId)
    {
        $this->logger()->info(sprintf('Start triggering imported contacts for batch ID: '.$importBatchId));
        $subscribers = $this->notTriggeredSubscribers()->where('import_batch_id', $importBatchId)->get();
        $total = count($subscribers);
        $this->logger()->info(sprintf('Triggering %s imported contacts', $total));

        $i = 0;
        foreach ($subscribers as $subscriber) {
            $i += 1;
            $this->initTrigger($subscriber);
            $this->logger()->info(sprintf('(%s/%s) triggered imported contact: %s', $i, $total, $subscriber->email));
        }
    }

    /**
     * Check for specific-datetimetime events.
     */
    public function checkForSpecificDatetime()
    {
        $this->logger()->info(sprintf('NEW > Check for Specific Date/Time'));

        $trigger = $this->getTriggerAction();
        $now = $this->customer->getCurrentTime();
        $eventDate = Carbon::parse(sprintf('%s %s', $trigger->getOption('date'), $trigger->getOption('at')))->timezone($this->customer->timezone);

        // Condition same date but after the specified time
        // So, two conditions are required:
        // + After the specified time
        // + But trigger on same day only
        $format = 'Y-m-d';

        if ($now->format($format) != $eventDate->format($format)) {
            $this->logger()->info(sprintf("Not triggering today, the scheduled date/time is %s, i.e. %s.", $eventDate->format('Y-m-d G:i:s P'), $eventDate->diffForHumans()));
            return;
        }

        $checked = $now->gte($eventDate);
        if (!$checked) {
            $this->logger()->info(sprintf("It is now %s, the scheduled time is: %s, i.e. %s.", $now->format('Y-m-d G:i:s P'), $eventDate->format('Y-m-d G:i:s P'), $eventDate->diffForHumans()));
            return;
        }

        $session = $this->triggerSessions()->latest()->first();
        if ($session && $now->gte($session->created_at) && $now->format($format) == $session->created_at->format($format)) {
            $this->logger()->info(sprintf('A session already exists: TriggerSession#%s, triggered same day %s at %s', $session->id, $session->created_at->diffForHumans(), $this->customer->formatDateTime($session->created_at, 'datetime_short')));
            return;
        }

        // Delete old sessions' triggers
        $this->triggerSessions()->delete();
        $this->autoTriggers()->delete(); // Delete legacy triggers that were not bound to a session

        // Create new sessions
        $session = $this->createTriggerSession();

        $this->forceTriggerAll($session);
    }

    public function logger()
    {
        $formatter = new LineFormatter("[%datetime%] %channel%.%level_name%: %message%\n");

        if (getenv('LOGFILE') != false) {
            $stream = new RotatingFileHandler(getenv('LOGFILE'), 0, Logger::DEBUG);
        } else {
            $logfile = $this->getLogFile();
            $stream = new RotatingFileHandler($logfile, 0, Logger::INFO);
        }

        $stream->setFormatter($formatter);

        $pid = getmypid();
        $logger = new Logger($pid);
        $logger->pushHandler($stream);

        return $logger;
    }

    public function getLogFile()
    {
        return storage_path('logs/' . php_sapi_name() . '/automation-'.$this->uid.'.log');
    }

    public function timelinesBy($subscriber)
    {
        $trigger = $this->autoTriggers()->where('subscriber_id', $subscriber->id)->first();

        return (is_null($trigger)) ? Timeline::whereRaw('1=2') : $trigger->timelines(); // trick - return an empty Timeline[] array
    }

    public function getInsight()
    {
        if (!$this->data) {
            return [];
        }

        $actions = json_decode($this->data, true);
        $insights = [];
        foreach ($actions as $action) {
            $insights[$action['id']] = $this->getActionStats($action);
        }

        return $insights;
    }

    public function getActionStats($attributes)
    {
        $total = $this->mailList->readCache('SubscriberCount');

        // The following implementation is prettier but 'countBy' is supported in Laravel 5.8 only
        // $count = $this->autoTriggers()->countBy(function($trigger) {
        //    return $trigger->isActionExecuted($action['id']);
        // });

        // IMPORTANT: this action does not associate with a particular trigger
        $action = $this->getAction($attributes);

        // @DEPRECATED
        // $count = 0;
        // foreach ($this->autoTriggers as $trigger) {
        //     $count += ($trigger->isActionExecuted($action->getId())) ? 1 : 0;
        // }

        // Count the number subscribers whose action has been executed
        $count = $this->subscribersByExecutedAction($action->getId())->count();

        if ($action->getType() == 'ElementTrigger') {
            $insight = [
                'count' => $count,
                'subtitle' => __('messages.automation.stats.triggered', ['count' => $count]),
                'percentage' => ($total != 0) ? ($count / $total) : 0,
                'latest_activity' => $this->autoTriggers()->max('created_at'),
            ];
        } elseif ($action->getType() == 'ElementOperation') {
            $count = 0;
            foreach ($this->autoTriggers as $trigger) {
                $count += (is_null($trigger->getActionById($action->getId())->getLastExecuted())) ? 0 : 1;
            }

            $insight = [
                'count' => $count,
                'subtitle' => __('messages.automation.stats.operation_executed_count', ['count' => $count]),
                'percentage' => ($total != 0) ? ($count / $total) : 0,
                'latest_activity' => $this->autoTriggers()->max('created_at'),
            ];
        } elseif ($action->getType() == 'ElementWait') {
            // Count the numbe contacts with this action as last executed one
            $queue = $this->subscribersByLatestAction($action->getId())->count();

            // since the previous $count also covers $queue ones, so get the already passed one by subtracting
            $passed = $count - $queue;

            $insight = [
                'count' => $count,
                'subtitle' => __('messages.automation.stats.in-queue2', ['queue' => $queue, 'passed' => $passed]),
                'percentage' => ($total != 0) ? ($count / $total) : 0,
                'latest_activity' => $this->autoTriggers()->max('created_at'),
            ];
        } elseif ($action->getType() == 'ElementAction') {
            $insight = [
                'count' => $count,
                'subtitle' => __('messages.automation.stats.sent', ['count' => $count]),
                'percentage' => ($total != 0) ? ($count / $total) : 0,
                'latest_activity' => $this->autoTriggers()->max('created_at'),
            ];
        } elseif ($action->getType() == 'ElementCondition') {
            $yes = $this->subscribersByExecutedAction($action->getChildYesId())->count();
            $no = $this->subscribersByExecutedAction($action->getChildNoId())->count();

            $insight = [
                'count' => $yes + $no,
                'subtitle' => __('messages.automation.stats.condition', ['yes' => $yes, 'no' => $no]),
                'percentage' => ($total != 0) ? (($yes + $no) / $total) : 0,
                'latest_activity' => $this->autoTriggers()->max('created_at'),
            ];
        } elseif ($action->getType() == 'ElementWebhook') {
            $insight = [
                'count' => $count,
                'subtitle' => __('messages.automation.stats.sent', ['count' => $count]),
                'percentage' => ($total != 0) ? ($count / $total) : 0,
                'latest_activity' => $this->autoTriggers()->max('created_at'),
            ];
        }

        return $insight;
    }

    public function getSummaryStats()
    {
        $total = $this->subscribers()->count();
        $involved = $this->autoTriggers()->count();

        $leaves = $this->getLeafActions();
        $complete = 0;
        foreach ($leaves as $leaf) {
            $complete += $this->subscribersByLatestAction($leaf)->count();
        }

        $completePercentage = ($total == 0) ? 0 : $complete / $total;

        return [
            'total' => $total,
            'involed' => $involved,
            'complete' => $completePercentage,
            'pending' => $this->getSubscribersWithTriggerInfo()->whereNull('auto_triggers.id')->count(),
        ];
    }

    // for debugging only
    public function getTriggerAction(): Trigger
    {
        $trigger = null;
        $this->getActions(function ($e) use (&$trigger) {
            if ($e->getType() == 'ElementTrigger') {
                $trigger = $e;
            }
        });

        return  $trigger;
    }

    public function getActions($callback)
    {
        $actions = $this->getElements(true);

        foreach ($actions as $action) {
            $instance = $this->getAction($action);
            $callback($instance);
        }
    }

    // For debugging only (at least for now)
    public function getActionById($id)
    {
        $actions = $this->getElements(true);

        foreach ($actions as $action) {
            $instance = $this->getAction($action);
            if ($instance->getId() == $id) {
                return $instance;
            }
        }

        // Not found
        return;
    }

    public function getLeafActions()
    {
        $leaves = [];
        $actions = $this->getElements(true);

        $this->getActions(function ($e) use (&$leaves) {
            if ($e->getNextActionId() == null) {
                $leaves[] = $e->getId();
            }
        });

        return $leaves;
    }

    // IMPORTANT: object returned by this function is not associated with a particular AutoTrigger
    public function getAction($attributes): Action
    {
        switch ($attributes['type']) {
            case 'ElementTrigger':
                $instance = new Trigger($attributes);
                break;
            case 'ElementAction':
                $instance = new Send($attributes);
                break;
            case 'ElementCondition':
                $instance = new Evaluate($attributes);
                break;
            case 'ElementWait':
                $instance = new Wait($attributes);
                break;
            case 'ElementOperation':
                $instance = new Operate($attributes);
                break;
            case 'ElementWebhook':
                $instance = new Webhook($attributes);
                break;
            default:
                throw new \Exception('Unknown Action type '.$attributes['type']);
        }

        return $instance;
    }

    // get all subscribers
    // if $actionId is provided, get only subscribers who have been triggered and have gone through the action
    public function subscribers($actionId = null)
    {
        $segments = $this->getSegments();

        if ($segments->isEmpty()) {
            $query = $this->mailList->subscribers()->select('subscribers.*');
        } else {
            $query = Subscriber::getByListsAndSegments($this->customer->getDbConnection(), ...$segments);
        }

        if (!is_null($actionId)) {
            // @deprecated, use the subscribersByLatestAction() method instead
            $query->join('auto_triggers', 'auto_triggers.subscriber_id', '=', 'subscribers.id')
                 ->where('auto_triggers.executed_index', 'LIKE', '%'.$actionId.'%')
                 ->where('auto_triggers.automation2_id', $this->id);
        }

        return $query;
    }

    public function subscribersToSend()
    {
        return $this->subscribers()->subscribed()->deliverableOrNotVerified();
    }

    public function subscribersByLatestAction($actionId)
    {
        $query = $this->autoTriggers()->join('subscribers', 'auto_triggers.subscriber_id', '=', 'subscribers.id')
                 ->where('auto_triggers.executed_index', 'LIKE', '%'.$actionId)->select('subscribers.*');
        return $query;
    }

    public function subscribersByExecutedAction($actionId)
    {
        $query = $this->autoTriggers()->join('subscribers', 'auto_triggers.subscriber_id', '=', 'subscribers.id')
                 ->where('auto_triggers.executed_index', 'LIKE', '%'.$actionId.'%')->select('subscribers.*');
        return $query;
    }

    public function getIntro()
    {
        $triggerType = $this->getTriggerAction()->getOption('key');
        $translationKey = 'messages.automation.intro.'.$triggerType;

        return __($translationKey, ['list' => $this->mailList->name]);
    }

    public function getBriefIntro()
    {
        $triggerType = $this->getTriggerAction()->getOption('key');
        $translationKey = 'messages.automation.brief-intro.'.$triggerType;

        return __($translationKey, ['list' => $this->mailList->name]);
    }

    public function countEmails()
    {
        $count = 0;
        $this->getActions(function ($e) use (&$count) {
            if ($e->getType() == 'ElementAction') {
                $count += 1;
            }
        });

        return $count;
    }

    /**
     * Get recent automations for switch.
     */
    public function getSwitchAutomations($customer)
    {
        return $customer->local()->automation2s()->where('id', '<>', $this->id)->orderBy('updated_at', 'desc')->limit(50);
    }

    /**
     * Get list fields options.
     */
    public function getListFieldOptions()
    {
        $data = [];

        foreach ($this->mailList->getFields()->get() as $field) {
            $data[] = ['text' => $field->label, 'value' => $field->uid];
        }

        return $data;
    }

    /**
     * Produce sample data.
     */
    public function produceSampleData()
    {
        if (is_null(config('app.demo')) || config('app.demo') == false) {
            throw new Exception("Please switch to DEMO mode in .env");
        }

        // Important: set DEMO=true
        // Reset all
        $this->resetListRelatedData();

        $count = $this->mailList->readCache('SubscriberCount');

        $min = (int) ($count * 0.2);
        $max = (int) ($count * 0.7);

        // Generate triggers
        $subscribers = $this->subscribers()->inRandomOrder()->limit(rand($min, $max))->get();
        foreach ($subscribers as $subscriber) {
            $this->initTrigger($subscriber);
        }

        // Run through trigger check
        $this->checkForExistingTriggersUpdate();

        // Update cache
        $this->updateCache();
    }

    /**
     * Clean up after list change.
     */
    public function resetListRelatedData()
    {
        // Delete autoTriggers will also delete
        // + tracking_logs
        // + open logs
        // + click logs
        // + timelines
        $this->triggerSessions()->delete();
        $this->autoTriggers()->delete();
        $this->updateCache();
        $this->setLastError(null);
    }

    /**
     * Change mail list.
     */
    public function updateMailList($new_list)
    {
        if ($this->mail_list_id != $new_list->id) {
            $this->mail_list_id = $new_list->id;
            $this->save();

            // reset automation list
            $this->resetListRelatedData();
        }
    }

    /**
     * Fill from request.
     */
    public function fillRequest($request)
    {
        // fill attributes
        $this->fill($request->all());

        // fill segments
        $segments = [];
        $this->segment_id = null;
        if (!empty($request->segment_uid)) {
            foreach ($request->segment_uid as $segmentUid) {
                $segments[] = \Acelle\Model\Segment::findByUid($segmentUid)->id;
            }

            if (!empty($segments)) {
                $this->segment_id = implode(',', $segments);
            }
        }
    }

    /**
     * Get segments.
     */
    public function getSegments()
    {
        if (!$this->segment_id) {
            return collect([]);
        }

        $segments = \Acelle\Model\Segment::whereIn('id', explode(',', $this->segment_id))->get();

        return $segments;
    }

    /**
     * Get segments uids.
     */
    public function getSegmentUids()
    {
        return $this->getSegments()->map->uid->toArray();
    }

    // For debugging only
    public function updateActionOptions($actionId, $data = [])
    {
        $json = json_decode($this->data, true);

        for ($i = 0; $i < sizeof($json); $i += 1) {
            $action = $json[$i];
            if ($action['id'] != $actionId) {
                continue;
            }

            $action['options'] = array_merge($action['options'], $data);

            $json[$i] = $action;
            $this->data = json_encode($json);
            $this->save();
        }
    }

    // Trigger all contacts that are not yet triggered
    public function forceTrigger()
    {
        $notTriggered = $this->notTriggeredSubscribers()->get();
        $total = $notTriggered->count();
        $i = 0;

        foreach ($notTriggered as $subscriber) {
            $i += 1;
            $this->initTrigger($subscriber);
            $this->logger()->info(sprintf('NEW > (%s/%s) > Adding new trigger for %s', $i, $total, $subscriber->email));
        }
    }

    public function forceTriggerAll($session)
    {
        // Triggers active contacts
        $pageCount = 0;
        paginate_query($this->subscribersToSend(), $perPage = 1000, $orderBy = 'subscribers.id', function ($pageNumber, $subscribers) use (&$pageCount, $session) {
            $pageCount += 1;

            $subscribersCount = $subscribers->count();
            $this->logger()->info(sprintf('Force triggering page %s with %s contacts', $pageNumber, $subscribersCount));
            foreach ($subscribers->get() as $subscriber) {
                $this->initTrigger($subscriber, $force = false, $session);
            }
            $this->logger()->info(sprintf('Finish Triggering %s contacts', $subscribersCount));
        });
    }

    public function notTriggeredSubscribers()
    {
        return $this->subscribersToSend()->leftJoin('auto_triggers', function ($join) {
            $join->on('subscribers.id', '=', 'auto_triggers.subscriber_id')->where('auto_triggers.automation2_id', '=', $this->id);
        })
                                      ->whereNull('auto_triggers.id')->select('subscribers.*');
    }

    public function allowApiCall()
    {
        // Usually invoked by API call
        $type = $this->getTriggerAction()->getOption('key');

        return $type == 'api-3-0';
    }

    /**
     * Update Campaign cached data.
     */
    public function getCacheIndex()
    {
        // cache indexes
        return [
            // @note: SubscriberCount must come first as its value shall be used by the others
            'SummaryStats' => function () {
                return $this->getSummaryStats();
            }
        ];
    }

    public function updateCacheInBackground()
    {
        dispatch(new \Acelle\Jobs\UpdateAutomation($this));
    }

    public function setLastError($message)
    {
        $this->last_error = $message;
        $this->save();
    }

    public function dump()
    {
        $this->subscribers();
    }

    /**
     * Frequency time unit options.
     *
     * @return array
     */
    public static function waitTimeUnitOptions($moreOptions = [])
    {
        return array_merge($moreOptions, [
            ['value' => 'day', 'text' => trans('messages.day')],
            ['value' => 'week', 'text' => trans('messages.week')],
            ['value' => 'month', 'text' => trans('messages.month')],
            ['value' => 'year', 'text' => trans('messages.year')],
        ]);
    }

    /**
     * Frequency time unit options.
     *
     * @return array
     */
    public static function cartWaitTimeUnitOptions($moreOptions = [])
    {
        return array_merge($moreOptions, [
            ['value' => 'minute', 'text' => trans('messages.minute')],
            ['value' => 'hour', 'text' => trans('messages.hour')],
            ['value' => 'day', 'text' => trans('messages.day')],
            ['value' => 'week', 'text' => trans('messages.week')],
            ['value' => 'month', 'text' => trans('messages.month')],
            ['value' => 'year', 'text' => trans('messages.year')],
        ]);
    }

    public function checkForAbandonedCart()
    {
    }

    /**
     * Get first email.
     *
     * @return email
     */
    public function getAbandonedCartEmail()
    {
        // var_dump($this->getElements()[1]->options);die();
        return \Acelle\Model\Email::findByUid($this->getElements()[1]->options->email_uid);
    }

    public function getTriggerType()
    {
        return $this->getTriggerAction()->getOption('key');
    }

    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    public function getSubscribersWithTriggerInfo()
    {
        $thisId = $this->id;
        $withSubscribers = $this->subscribers()
            ->leftJoin('auto_triggers', function ($join) use ($thisId) {
                $join->on('auto_triggers.subscriber_id', 'subscribers.id');
                $join->where('auto_triggers.automation2_id', $thisId);
            })->with(['autoTriggers' => function ($query) {
                $query->where('automation2_id', '=', $this->id);
            }])
            ->select('subscribers.id AS id', 'subscribers.uid AS uid', 'subscribers.email AS email', 'subscribers.created_at AS subscriber_created_at')
            ->addSelect('auto_triggers.id AS auto_trigger_id')
            ->addSelect('auto_triggers.created_at AS triggered_at')
            ->addSelect('auto_triggers.updated_at AS last_activity_at')
            ->addSelect('mail_list_id');


        $noSubscribers = $this->autoTriggers()
            ->join('automation2s', 'automation2_id', '=', 'automation2s.id')
            ->whereNull('subscriber_id')
            ->select(DB::raw('NULL AS id, NULL AS uid, NULL AS email, NULL AS subscriber_created_at'))
            ->addSelect('auto_triggers.id AS auto_trigger_id')
            ->addSelect('auto_triggers.created_at AS triggered_at')
            ->addSelect('auto_triggers.updated_at AS last_activity_at')
            ->addSelect('mail_list_id');

        //return $withSubscribers;
        //return $noSubscribers;
        return $withSubscribers->union($noSubscribers);//->union($noSubscribers);
    }

    public function getAutoTriggerFor($subscriber)
    {
        $trigger = $this->autoTriggers()->where('subscriber_id', $subscriber->id)->latest()->first();
        return $trigger;
    }

    public static function getTriggerTypes()
    {
        $types = [
            'welcome-new-subscriber',
            'say-happy-birthday',
            'subscriber-added-date',
            'specific-date',
            'say-goodbye-subscriber',
            'api-3-0',
            'weekly-recurring',
            'monthly-recurring',
            self::TRIGGER_TAG_BASED,
            self::TRIGGER_REMOVE_TAG,
            self::TRIGGER_ATTRIBUTE_UPDATE,
        ];

        if (config('custom.woo')) {
            $types[] = 'woo-abandoned-cart';
        }

        return $types;
    }

    public static function newDefault($fields = [])
    {
        $automation = new static();

        if (!isset($fields['name'])) {
            $automation->name = trans('messages.automation.untitled');
        } else {
            $automation->name = $fields['name'];
        }

        $automation->status = self::STATUS_INACTIVE;

        return $automation;
    }

    public function createFromArray($params)
    {
        // make validator
        $validator = \Validator::make($params, $this->rules());

        // redirect if fails
        if ($validator->fails()) {
            return $validator;
        }

        $this->fill($params);

        // pass validation and save
        $this->mail_list_id = \Acelle\Model\MailList::findByUid($params['mail_list_uid'])->id;

        $data = [
            $this->createTrigger($params)
        ];
        $this->data = json_encode($data);
        $this->save();
        $this->updateCache();

        return $validator;
    }

    // Temporary function
    public function createTrigger($params)
    {
        $type = $params['trigger_type'];
        if ($type == 'welcome-new-subscriber') {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.tree.welcome-new-subscriber'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                  'key' => 'welcome-new-subscriber',
                  'type' => 'list-subscription',
                  'init' => true,
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == 'say-happy-birthday') {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.tree.say-happy-birthday'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => 'say-happy-birthday',
                    'type' => 'event',
                    'field' => $params['options']['field'],
                    'before' => $params['options']['before'],
                    'at' => $params['options']['at'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == 'specific-date') {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.tree.specific-date'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => 'specific-date',
                    'type' => 'datetime',
                    'date' => $params['options']['date'],
                    'at' => $params['options']['at'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == 'subscriber-added-date') {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.tree.subscriber-added-date'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => 'subscriber-added-date',
                    'type' => 'event',
                    'field' => 'created_at',
                    'delay' => $params['options']['delay'],
                    'at' => $params['options']['at'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == 'say-goodbye-subscriber') {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.tree.say-goodbye-subscriber'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => 'say-goodbye-subscriber',
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == 'api-3-0') {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.tree.api-3-0'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => 'api-3-0',
                    'type' => 'api',
                    'endpoint' => $params['options']['endpoint'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == 'weekly-recurring') {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.weekly-recurring'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => 'weekly-recurring',
                    'type' => 'datetime',
                    'days_of_week' => $params['options']['days_of_week'],
                    'at' => $params['options']['at'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == 'monthly-recurring') {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.monthly-recurring'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => 'monthly-recurring',
                    'type' => 'datetime',
                    'days_of_month' => $params['options']['days_of_month'],
                    'at' => $params['options']['at'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == 'woo-abandoned-cart') {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.woo-abandoned-cart'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => 'woo-abandoned-cart',
                    'type' => 'woo-abandoned-cart',
                    'source_uid' => $params['options']['source_uid'],
                    'wait' => $params['options']['wait'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == self::TRIGGER_TAG_BASED) {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.tag-based'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => self::TRIGGER_TAG_BASED,
                    'type' => self::TRIGGER_TAG_BASED,
                    'tags' => $params['options']['tags'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == self::TRIGGER_REMOVE_TAG) {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.remove-tag'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => self::TRIGGER_REMOVE_TAG,
                    'type' => self::TRIGGER_REMOVE_TAG,
                    'tags' => $params['options']['tags'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        } elseif ($type == self::TRIGGER_ATTRIBUTE_UPDATE) {
            return [
                'id' => 'trigger',
                'title' => trans('messages.automation.trigger.attribute-update'),
                'type' => 'ElementTrigger',
                'child' => null,
                'options' => [
                    'key' => self::TRIGGER_ATTRIBUTE_UPDATE,
                    'type' => self::TRIGGER_ATTRIBUTE_UPDATE,
                    'field_uid' => $params['options']['field_uid'],
                    'value' => $params['options']['value'],
                    'init' => 'true',
                ],
                'last_executed' => null,
                'evaluationResult' => null,
            ];
        }

        throw new Exception("Invalid trigger type: ".$type);
    }

    public static function getConditionWaitOptions($custom = null)
    {
        $result = [
            ['text' => trans_choice('messages.count_hour', 12), 'value' => '12 hours'],
            ['text' => trans_choice('messages.count_day', 1), 'value' => '1 day'],
            ['text' => trans_choice('messages.count_day', 2), 'value' => '2 days'],
            ['text' => trans_choice('messages.count_day', 3), 'value' => '3 days'],
        ];

        if ($custom) {
            if (!in_array($custom, array_map(function ($item) {
                return $item['value'];
            }, $result))) {
                $vals = explode(' ', $custom);
                $result[] = [
                    'text' => trans_choice('messages.count_' . $vals[1], $vals[0]),
                    'value' => $custom,
                ];
            }
        }

        $result[] = ['text' => trans('messages.wait.custom'), 'value' => 'custom'];

        return $result;
    }

    public function deleteAndCleanup()
    {
        // clone email if exist
        $this->getActions(function ($action) {
            $options = $action->getOptions();
            if (isset($options['email_uid'])) {
                $originalEmail = Email::findByUid($options['email_uid']);

                if ($originalEmail) {
                    $originalEmail->deleteAndCleanup();
                }
            }
        });

        $this->delete();
    }

    public function copy($newName, $mailListUid)
    {
        // new automation
        $newAutomation = static::newDefault();

        // validate inputs
        $validator = \Validator::make([
            'name' => $newName,
            'mail_list_uid' => $mailListUid,
        ], [
            'name' => 'required',
            'mail_list_uid' => 'required',
        ]);

        // redirect if fails
        if ($validator->fails()) {
            return [$validator, $newAutomation];
        }

        // Duplicate data
        $newAutomation->customer_id = $this->customer_id;
        $newAutomation->name = $newName;
        $newAutomation->mail_list_id = MailList::findByUid($mailListUid)->id;
        $newAutomation->mail_list_id = $this->mail_list_id;
        $newAutomation->time_zone = $this->time_zone;
        $newAutomation->data = $this->data;
        $newAutomation->segment_id = $this->segment_id;
        $newAutomation->status = static::STATUS_INACTIVE;
        $newAutomation->save();

        // clone email if exist
        $newAutomation->getActions(function ($action) use ($newAutomation) {
            $options = $action->getOptions();
            if (isset($options['email_uid'])) {
                $originalEmail = Email::findByUid($options['email_uid']);
                $newEmail = $originalEmail->copy();

                // fill list attributes to email
                $newEmail->automation2_id = $newAutomation->id;
                $newEmail->customer_id = $newAutomation->customer_id;
                $newEmail->save();

                // trick to change email
                // $action->setOption('email_uid', $newEmail->uid);
                $newAutomation->data = str_replace($options['email_uid'], $newEmail->uid, $newAutomation->data);
                $newAutomation->save();
            }
        });

        return [$validator, $newAutomation];
    }

    public function getAvailableOutgoingWebhookTags()
    {
        $tags = [];

        // List field tags
        foreach ($this->mailList->fields as $field) {
            $tags[] = [
                'label' => $field->label,
                'tag' => '{{' . $field->tag . '}}',
            ];
        }

        return $tags;
    }
}
