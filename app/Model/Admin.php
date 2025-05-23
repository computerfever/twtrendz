<?php

/**
 * Admin class.
 *
 * Model class for admin
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
use Acelle\Model\Subscription;
use Acelle\Library\Traits\TrackJobs;
use Acelle\Jobs\ImportBlacklistJob;
use Acelle\Library\Traits\HasUid;
use Carbon\Carbon;

class Admin extends Model
{
    use TrackJobs;
    use HasUid;
    protected $connection = 'mysql';

    public const STATUS_ACTIVE = 'active';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'timezone', 'language_id', 'color_scheme', 'text_direction', 'menu_layout', 'theme_mode'
    ];

    /**
     * Associations.
     *
     * @var object | collect
     */
    public function contact()
    {
        return $this->belongsTo('Acelle\Model\Contact');
    }

    public function user()
    {
        return $this->belongsTo('Acelle\Model\User');
    }

    public function adminGroup()
    {
        return $this->belongsTo('Acelle\Model\AdminGroup');
    }

    public function customers()
    {
        return $this->hasMany('Acelle\Model\Customer');
    }

    public function templates()
    {
        return $this->hasMany('Acelle\Model\Template');
    }

    public function language()
    {
        return $this->belongsTo('Acelle\Model\Language');
    }

    public function creator()
    {
        return $this->belongsTo('Acelle\Model\User', 'creator_id');
    }

    /**
     * Check if admin has customer account.
     *
     * @return bool
     */
    public function hasCustomerAccount()
    {
        return $this->user && $this->user->customer;
    }

    /**
     * Get all items.
     *
     * @return collect
     */
    public static function getAll()
    {
        return self::select('*');
    }

    /**
     * Items per page.
     *
     * @var array
     */
    public static $itemsPerPage = 25;

    /**
     * Filter items.
     *
     * @return collect
     */
    public static function filter($request)
    {
        $query = self::select('admins.*')
                        ->join('users', 'users.id', '=', 'admins.user_id')
                        ->leftJoin('admin_groups', 'admin_groups.id', '=', 'admins.admin_group_id');

        // Keyword
        if (!empty(trim($request->keyword))) {
            foreach (explode(' ', trim($request->keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('users.first_name', 'like', '%'.$keyword.'%')
                        ->orWhere('admin_groups.name', 'like', '%'.$keyword.'%')
                        ->orWhere('users.last_name', 'like', '%'.$keyword.'%');
                });
            }
        }

        // filters
        $filters = $request->all();
        if (!empty($filters)) {
            if (!empty($filters['admin_group_id'])) {
                $query = $query->where('admins.admin_group_id', '=', $filters['admin_group_id']);
            }
        }

        if (!empty($request->creator_id)) {
            $query = $query->where('admins.creator_id', '=', $request->creator_id);
        }

        return $query;
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public static function search($request)
    {
        $query = self::filter($request);

        if (!empty($request->sort_order)) {
            $query = $query->orderBy($request->sort_order, $request->sort_direction);
        }

        return $query;
    }

    /**
     * Get admin setting.
     *
     * @return string
     */
    public function getOption($name)
    {
        return $this->adminGroup->getOption($name);
    }

    /**
     * Get admin permission.
     *
     * @return string
     */
    public function getPermission($name)
    {
        return $this->adminGroup->getPermission($name);
    }

    /**
     * Get user's color scheme.
     *
     * @return string
     */
    public function getColorScheme()
    {
        // Store mode support only sms theme
        if (config('app.store')) {
            return 'store';
        }

        if (!empty($this->color_scheme)) {
            return $this->color_scheme;
        } else {
            return \Acelle\Model\Setting::get('backend_scheme');
        }
    }

    /**
     * Color array.
     *
     * @return array
     */
    public static function colors($default)
    {
        return [
            ['value' => '', 'text' => trans('messages.system_default')],
            ['value' => 'blue', 'text' => trans('messages.blue')],
            ['value' => 'green', 'text' => trans('messages.green')],
            ['value' => 'brown', 'text' => trans('messages.brown')],
            ['value' => 'pink', 'text' => trans('messages.pink')],
            ['value' => 'grey', 'text' => trans('messages.grey')],
            ['value' => 'white', 'text' => trans('messages.white')],
        ];
    }

    /**
     * Disable admin.
     *
     * @return bool
     */
    public function disable()
    {
        $this->status = 'inactive';

        return $this->save();
    }

    /**
     * Enable admin.
     *
     * @return bool
     */
    public function enable()
    {
        $this->status = 'active';

        return $this->save();
    }

    /**
     * Get recent resellers.
     *
     * @return collect
     */
    public function getAllCustomers()
    {
        $query = \Acelle\Model\Customer::select('customers.*');

        if (!$this->user->can('readAll', new \Acelle\Model\Customer())) {
            $query = $query->where('customers.admin_id', '=', $this->id);
        }

        return $query;
    }

    /**
     * Get recent resellers.
     *
     * @return collect
     */
    public function recentCustomers()
    {
        return $this->getAllCustomers()->orderBy('created_at', 'DESC')->limit(5)->get();
    }

    /**
     * Get all admin's subcriptions.
     *
     * @return collect
     */
    public function getAllSubscriptions()
    {
        if ($this->user->can('readAll', new \Acelle\Model\Customer())) {
            $query = Subscription::select('subscriptions.*')->leftJoin('customers', 'customers.id', '=', 'subscriptions.customer_id');
        } else {
            $query = Subscription::select('subscriptions.*')
                ->join('customers', 'customers.id', '=', 'subscriptions.customer_id')
                ->where('customers.admin_id', '=', $this->id);
            /* ERROR
            $query = $query->where(function ($q) {
                $q->orwhere('customers.admin_id', '=', $this->id)
                    ->orWhere('subscriptions.admin_id', '=', $this->id);
            });
            */
        }

        return $query;
    }

    /**
     * Get subscription notification count.
     *
     * @return collect
     */
    public function subscriptionNotificationCount()
    {
        // Notice that timezone of datetime object passed to ->where() must match Laravel's app.timezone
        $query = $this->getAllSubscriptions()
            ->where('subscriptions.ends_at', '>=', \Carbon\Carbon::now()->endOfDay())
            ->count();

        return $query == 0 ? '' : $query;
    }

    /**
     * Get recent subscriptions.
     *
     * @return collect
     */
    public function recentSubscriptions($number = 5)
    {
        $query = $this->getAllSubscriptions()
            ->whereNull('subscriptions.updated_at')->orWhere('subscriptions.updated_at', '>=', \Carbon\Carbon::now())
            ->orderBy('subscriptions.created_at', 'desc')->limit($number);

        return $query->get();
    }

    /**
     * Get admin language code.
     *
     * @return string
     */
    public function getLanguageCode()
    {
        return $this->language ? $this->language->code : null;
    }

    /**
     * Get customer language code.
     *
     * @return string
     */
    public function getLanguageCodeFull()
    {
        $region_code = $this->language->region_code ? strtoupper($this->language->region_code) : strtoupper($this->language->code);
        return $this->language ? ($this->language->code.'-'.$region_code) : null;
    }

    /**
     * Get admin logs of their customers.
     *
     * @return string
     */
    public function getLogs()
    {
        $query = \Acelle\Model\Log::select('logs.*')->join('customers', 'customers.id', '=', 'logs.customer_id')
            ->leftJoin('admins', 'admins.id', '=', 'customers.admin_id');

        if (!$this->user->can('readAll', new \Acelle\Model\Customer())) {
            $query = $query->where('admins.id', '=', $this->id);
        }

        return $query;
    }

    /**
     * Create customer account.
     */
    public function createCustomerAccount()
    {
        $customer = \Acelle\Model\Customer::newCustomer();
        $customer->admin_id = $this->id;
        $customer->language_id = $this->language_id;
        $customer->name = $this->user->displayName(get_localization_config('show_last_name_first', $this->getLanguageCode()));
        $customer->timezone = $this->timezone;
        $customer->status = $this->status;
        $customer->save();

        // add cutomer to user
        $user = $this->user;
        $user->customer_id = $customer->id;
        $user->save();

        return $customer;
    }

    /**
     * Check if customer is disabled.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status == Customer::STATUS_ACTIVE;
    }

    /**
     * Custom can for admin.
     *
     * @return bool
     */
    public function can($action, $item = null)
    {
        if ($item) {
            return \Auth::user()->can($action, [$item, 'admin']);
        } else {
            return \Auth::user()->can($action, ['admin']);
        }
    }

    /**
     * Destroy admin.
     *
     * @return bool
     */
    public function deleteAccount()
    {
        // unset all customers
        $this->customers()->update(['admin_id' => null]);

        // Delete admin and user
        $user = $this->user;

        $this->delete();

        if (!$user->customer()->exists()) {
            $user->deleteAndCleanup();
        }
    }

    /**
     * Get all subscription count by plan.
     *
     * @return int
     */
    public function getAllSubscriptionsByPlan($plan)
    {
        return $this->getAllSubscriptions()->where('subscriptions.plan_id', '=', $plan->id);
    }

    /**
     * Get all plans.
     *
     * @return int
     */
    public function getAllPlans()
    {
        return \Acelle\Model\PlanGeneral::active();
    }

    /**
     * Get all admin.
     *
     * @return int
     */
    public function getAllAdmins()
    {
        $query = \Acelle\Model\Admin::getAll()
            ->where('admins.status', '=', \Acelle\Model\Admin::STATUS_ACTIVE);

        if (!$this->can('readAll', new \Acelle\Model\Admin())) {
            $query = $query->where('admins.creator_id', '=', $this->user_id);
        }

        return $query;
    }

    /**
     * Get all admin.
     *
     * @return int
     */
    public function getAllAdminGroups()
    {
        $query = \Acelle\Model\AdminGroup::select('*');

        if (!$this->can('readAll', new \Acelle\Model\AdminGroup())) {
            $query = $query->where('admin_groups.creator_id', '=', $this->user_id);
        }

        return $query;
    }

    /**
     * Get all sending servers.
     *
     * @return int
     */
    public function getAllSendingServers()
    {
        $query = \Acelle\Model\SendingServer::active();

        if (!$this->can('readAll', new \Acelle\Model\SendingServer())) {
            $query = $query->where('sending_servers.admin_id', '=', $this->id);
        }

        // remove customer sending servers
        $query = $query->whereNull('customer_id');

        return $query;
    }

    /**
     * Get all campaigns.
     *
     * @return collect
     */
    public function getAllCampaigns()
    {
        $query = \Acelle\Model\Campaign::getAll();

        if (!$this->can('readAll', new \Acelle\Model\Customer())) {
            $query = $query->leftJoin('customers', 'customers.id', '=', 'campaigns.customer_id')
                ->where('customers.admin_id', '=', $this->id);
        }

        return $query;
    }

    /**
     * Get all lists.
     *
     * @return collect
     */
    public function getAllLists()
    {
        $query = \Acelle\Model\MailList::getAll();

        if (!$this->can('readAll', new \Acelle\Model\Customer())) {
            $query = $query->leftJoin('customers', 'customers.id', '=', 'mail_lists.customer_id')
                ->where('customers.admin_id', '=', $this->id);
        }

        return $query;
    }

    /**
     * Get system notification.
     *
     * @return int
     */
    public function notifications()
    {
        return Notification::orderBy('created_at', 'desc');
    }

    public function importBlacklistJobs()
    {
        return $this->jobMonitors()->orderBy('job_monitors.id', 'DESC')->where('job_type', ImportBlacklistJob::class);
    }

    public function getMenuLayout()
    {
        return ($this->menu_layout == 'left' ? 'left' : 'top');
    }

    public static function newAdmin()
    {
        $admin = new self();
        $admin->menu_layout = \Acelle\Model\Setting::get('layout.menu_bar');
        $admin->status = static::STATUS_ACTIVE;

        return $admin;
    }

    // Get the current time in Customer timezone
    public function getCurrentTime()
    {
        return Carbon::now($this->timezone);
    }

    public function formatDateTime(?Carbon $datetime, string $name)
    {
        // $name is a format name like: date_full | date_short
        // See config('localization')['*'] for the full list of available format names

        return format_datetime($datetime, $name, $this->getLanguageCode());
    }

    /**
     * Get customer timezone.
     *
     * @return string
     */
    public function getTimezone()
    {
        return $this->timezone;
    }

    public function displayName()
    {
        $lastNameFirst = get_localization_config('show_last_name_first', $this->getLanguageCode());

        if ($lastNameFirst) {
            return htmlspecialchars(trim($this->user->last_name.' '.$this->user->first_name));
        } else {
            return htmlspecialchars(trim($this->user->first_name.' '.$this->user->last_name));
        }
    }

    public function saveAdmin(
        $user,
        $admin_group_id,
        $creator_id,
        $timezone,
        $language_id,
        $create_customer_account = null
    ) {
        $rules = [
            'user_id' => 'required',
            'admin_group_id' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
        ];

        // fill
        $this->user_id = $user->id;
        $this->admin_group_id = $admin_group_id;
        $this->timezone = $timezone;
        $this->language_id = $language_id;
        if ($creator_id) {
            $this->creator_id = $creator_id;
        }

        $validator = \Validator::make([
            'user_id' => $user->id,
            'admin_group_id' => $admin_group_id,
            'timezone' => $timezone,
            'language_id' => $language_id,
        ], $rules);

        if ($validator->fails()) {
            return [false, $validator->errors()];
        }

        // save
        $this->save();

        // create customer account
        if (!empty($create_customer_account) && $create_customer_account == 'yes') {
            // create customer account
            $customer = $this->createCustomerAccount();

            // set customer_id to the user
            $user->customer_id = $customer->id;
            $user->save();
        }

        // success
        return [true, $validator->errors()];
    }
}
