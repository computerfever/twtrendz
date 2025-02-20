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
use Carbon\Carbon;
use Acelle\Model\Subscription;
use Acelle\Model\Source;
use Exception;
use DB;
use Acelle\Library\Facades\Billing;
use Acelle\Library\AutoBillingData;
use Acelle\Library\Traits\TrackJobs;
use Acelle\Jobs\ImportBlacklistJob;
use Acelle\Library\Traits\HasUid;
use Acelle\Library\Facades\SubscriptionFacade;
use Acelle\Library\Facades\Hook;

class Customer extends Model
{
    use TrackJobs;
    use HasUid;

    protected $connection = 'mysql';

    protected $quotaTracker;

    // Plan status
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_ACTIVE = 'active';

    public const BASE_DIR = 'app/customers'; // storage/customers/000000
    public const ATTACHMENTS_DIR = 'home/attachments';  // storage/customers/000000/home/files
    public const TEMPLATES_DIR = 'home/templates';  // storage/customers/000000/home/files
    public const PRODUCT_DIR = 'home/products';
    public const LOGS_DIR = 'home/logs/';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'timezone', 'language_id', 'color_scheme', 'text_direction', 'menu_layout', 'theme_mode'
    ];

    public function subscriptions()
    {
        return $this->hasMany('Acelle\Model\Subscription', 'customer_id');
    }

    public function generalSubscriptions()
    {
        return $this->subscriptions()->general();
    }

    public function getNewGeneralSubscription()
    {
        // ONLY one new subscription allowed
        $subscriptions = $this->generalSubscriptions()->new();

        if ($subscriptions->count() > 1) {
            throw new Exception('There are 2 subscriptions of [new] status. Please clean up the DB');
        }

        return $subscriptions->first();
    }

    public function getLastCancelledOrEndedGeneralSubscription()
    {
        // Lấy subscription ended gần đây nhất. Null nếu chưa đăng ký bao giờ
        return $this->generalSubscriptions()->cancelledOrEdned()->orderBy('created_at', 'desc')->first();
    }

    public function contact()
    {
        return $this->belongsTo('Acelle\Model\Contact');
    }

    public function mailLists()
    {
        return $this->hasMany('Acelle\Model\MailList', 'customer_id');
    }

    public function users()
    {
        return $this->hasMany('Acelle\Model\User');
    }

    // remove it after update cashier
    public function user()
    {
        return $this->hasOne('Acelle\Model\User');
    }

    public function getFirstUserLegacy()
    {
        return $this->users()->first();
    }

    public function admin()
    {
        return $this->belongsTo('Acelle\Model\Admin');
    }

    public function templates()
    {
        return $this->hasMany('Acelle\Model\Template', 'customer_id');
    }

    public function language()
    {
        return $this->belongsTo('Acelle\Model\Language');
    }

    public function logs()
    {
        return $this->hasMany('Acelle\Model\Log', 'customer_id')->orderBy('created_at', 'desc');
    }

    public function emailVerificationServers()
    {
        return $this->hasMany('Acelle\Model\EmailVerificationServer', 'customer_id');
    }

    public function activeEmailVerificationServers()
    {
        return $this->emailVerificationServers()->where('status', '=', EmailVerificationServer::STATUS_ACTIVE);
    }

    public function blacklists()
    {
        return $this->hasMany('Acelle\Model\Blacklist', 'customer_id');
    }

    public function invoices()
    {
        return $this->hasMany('Acelle\Model\Invoice', 'customer_id');
    }

    public function transactions()
    {
        return $this->hasManyThrough('Acelle\Model\Transaction', 'Acelle\Model\Invoice', 'customer_id');
    }

    public function roles()
    {
        return $this->hasMany('Acelle\Model\Role', 'customer_id');
    }

    // billing addresses
    public function billingAddresses()
    {
        return $this->hasMany('Acelle\Model\BillingAddress', 'customer_id');
    }

    public function newBillingAddress()
    {
        $address = new \Acelle\Model\BillingAddress();
        $address->customer_id = $this->id;
        return $address;
    }

    public function getDefaultBillingAddress()
    {
        return $this->billingAddresses()->first();
    }

    public function updateBillingInformationFromInvoice($invoice)
    {
        $billingAddress = $this->getDefaultBillingAddress();

        // has no address yet
        if (!$billingAddress) {
            $billingAddress = $this->newBillingAddress();
        }

        $billingAddress->fill([
            'first_name' => $invoice->billing_first_name,
            'last_name' => $invoice->billing_last_name,
            'address' => $invoice->billing_address,
            'email' => $invoice->billing_email,
            'phone' => $invoice->billing_phone,
            'country_id' => $invoice->billing_country_id,
        ]);

        $billingAddress->save();
    }

    public function sources()
    {
        return $this->hasMany('Acelle\Model\Source', 'customer_id');
    }

    public function signatures()
    {
        return $this->hasMany(Signature::class, 'customer_id');
    }

    public function getBasePath($path = null)
    {
        $base = storage_path(join_paths(self::BASE_DIR, $this->uid)); // storage/app/customers/000000/

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    public function getTemplatesPath($path = null)
    {
        $base = $this->getBasePath(self::TEMPLATES_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    public function getProductsPath($path = null)
    {
        $base = $this->getBasePath(self::PRODUCT_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    public function getAttachmentsPath($path = null)
    {
        $base = $this->getBasePath(self::ATTACHMENTS_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
    }

    public function getLogPath($path = null)
    {
        $base = $this->getBasePath(self::LOGS_DIR);

        if (!\Illuminate\Support\Facades\File::exists($base)) {
            \Illuminate\Support\Facades\File::makeDirectory($base, 0777, true, true);
        }

        return join_paths($base, $path);
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
    public static function scopeFilter($query, $request)
    {
        // filters
        $filters = $request->all();
        if (!empty($filters)) {
        }

        // Admin filter
        if (!empty($request->admin_id)) {
            $query = $query->where('customers.admin_id', '=', $request->admin_id);
        }
    }

    /**
     * Search items.
     *
     * @return collect
     */
    public static function scopeSearch($query, $keyword)
    {
        $query = $query->select('customers.*')
            ->leftJoin('users', 'users.customer_id', '=', 'customers.id')
            ->distinct();

        // Keyword
        if (!empty(trim($keyword))) {
            foreach (explode(' ', trim($keyword)) as $keyword) {
                $query = $query->where(function ($q) use ($keyword) {
                    $q->orwhere('users.first_name', 'like', '%'.$keyword.'%')
                        ->orWhere('users.last_name', 'like', '%'.$keyword.'%')
                        ->orWhere('users.email', 'like', '%'.$keyword.'%');
                });
            }
        }
    }

    public function getCurrentActiveSubscription()
    {
        // Keep this function alias to support plugins
        return $this->getCurrentActiveGeneralSubscription();
    }

    public function maxSubscribers()
    {
        $count = get_tmp_quota($this, 'subscriber_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Get customer's quota.
     *
     * @return string
     */
    public function maxQuota()
    {
        $quota = get_tmp_quota($this, 'sending_quota');
        if ($quota == '-1') {
            return '∞';
        } else {
            return $quota;
        }
    }

    /**
     * Get customer's color scheme.
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
            return \Acelle\Model\Setting::get('frontend_scheme');
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
            ['value' => 'default', 'text' => trans('messages.system_default')],
            ['value' => 'blue', 'text' => trans('messages.blue')],
            ['value' => 'green', 'text' => trans('messages.green')],
            ['value' => 'brown', 'text' => trans('messages.brown')],
            ['value' => 'pink', 'text' => trans('messages.pink')],
            ['value' => 'grey', 'text' => trans('messages.grey')],
            ['value' => 'white', 'text' => trans('messages.white')],
        ];
    }

    /**
     * Disable customer.
     *
     * @return bool
     */
    public function disable()
    {
        $this->status = 'inactive';

        return $this->save();
    }

    /**
     * Enable customer.
     *
     * @return bool
     */
    public function enable()
    {
        $this->status = 'active';

        return $this->save();
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

    /**
     * Get customer language code.
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
     * Get customer select2 select options.
     *
     * @return array
     */
    public static function select2($request)
    {
        $data = ['items' => [], 'more' => true];

        $query = self::select('customers.*')->leftJoin('users', 'users.customer_id', '=', 'customers.id');
        if (isset($request->q)) {
            $keyword = $request->q;
            $query = $query->where(function ($q) use ($keyword) {
                $q->orwhere('users.first_name', 'like', '%'.$keyword.'%')
                    ->orWhere('users.last_name', 'like', '%'.$keyword.'%')
                    ->orWhere('users.email', 'like', '%'.$keyword.'%');
            });
        }

        // Read all check
        if (!$request->user()->admin->can('readAll', new \Acelle\Model\Customer())) {
            $query = $query->where('customers.admin_id', '=', $request->user()->admin->id);
        }

        foreach ($query->limit(20)->get() as $customer) {
            $data['items'][] = ['id' => $customer->uid, 'text' => $customer->name];
        }

        return json_encode($data);
    }

    /**
     * Create/Update customer information.
     *
     * @return object
     */
    public function createAccountAndUser($request)
    {
        $user = $this->newUser();

        DB::transaction(function () use ($request, &$user) {
            // User
            $user->fill($request->all());
            $user->password = bcrypt($request->password);
            $user->save();

            // Customer
            $this->fill($request->all());
            $this->status = self::STATUS_ACTIVE;
            $this->name = $user->displayName(get_localization_config('show_last_name_first', $this->getLanguageCode()));
            $this->save();

            //
            $user->save();

            // default role
            $user->setRole(Role::getDefaultAdminRole());
        });

        // Important: return the newly created USER
        return $user;
    }

    public function sendingServers()
    {
        return $this->hasMany('Acelle\Model\SendingServer', 'customer_id');
    }

    /**
     * Customers count by time.
     *
     * @return number
     */
    public static function customersCountByTime($begin, $end, $admin = null)
    {
        $query = \Acelle\Model\Customer::select('customers.*');

        if (isset($admin) && !$admin->can('readAll', new \Acelle\Model\Customer())) {
            $query = $query->where('customers.admin_id', '=', $admin->id);
        }

        $query = $query->where('customers.created_at', '>=', $begin)
            ->where('customers.created_at', '<=', $end);

        return $query->count();
    }

    /**
     * Sending servers count.
     *
     * @var int
     */
    public function sendingServersCount()
    {
        return $this->sendingServers()->count();
    }

    /**
     * Get max sending server count.
     *
     * @var int
     */
    public function maxSendingServers()
    {
        $count = get_tmp_quota($this, 'sending_servers_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Get max email verification server count.
     *
     * @var int
     */
    public function maxEmailVerificationServers()
    {
        $count = get_tmp_quota($this, 'email_verification_servers_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate email verification server usage.
     *
     * @return number
     */
    public function emailVerificationServersUsage()
    {
        $max = $this->maxEmailVerificationServers();
        $count = $this->emailVerificationServersCount();

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
     * Calculate email verigfication servers usage.
     *
     * @return number
     */
    public function displayEmailVerificationServersUsage()
    {
        if ($this->maxEmailVerificationServers() == '∞') {
            return trans('messages.unlimited');
        }

        return $this->emailVerificationServersUsage().'%';
    }

    /**
     * Calculate sending servers usage.
     *
     * @return number
     */
    public function sendingServersUsage()
    {
        $max = $this->maxSendingServers();
        $count = $this->sendingServersCount();

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
     * Calculate sending servers usage.
     *
     * @return number
     */
    public function displaySendingServersUsage()
    {
        if ($this->maxSendingServers() == '∞') {
            return trans('messages.unlimited');
        }

        return $this->sendingServersUsage().'%';
    }

    /**
     * Get max sending server count.
     *
     * @var int
     */
    public function maxSendingDomains()
    {
        $count = get_tmp_quota($this, 'sending_domains_max');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Get all customer active sending servers.
     *
     * @return collect
     */
    public function activeSendingServers()
    {
        return $this->sendingServers()->where('status', '=', \Acelle\Model\SendingServer::STATUS_ACTIVE);
    }

    /**
     * Check if customer is disabled.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status == self::STATUS_ACTIVE;
    }

    /**
     * Get total file size usage.
     *
     * @return number
     */
    public function totalUploadSize()
    {
        return \Acelle\Library\Tool::getDirectorySize(base_path('public/source/'.$this->uid)) / 1048576;
    }

    /**
     * Get max upload size quota.
     *
     * @return number
     */
    public function maxTotalUploadSize()
    {
        $count = get_tmp_quota($this, 'max_size_upload_total');
        if ($count == -1) {
            return '∞';
        } else {
            return $count;
        }
    }

    /**
     * Calculate campaign usage.
     *
     * @return number
     */
    public function totalUploadSizeUsage()
    {
        if ($this->maxTotalUploadSize() == '∞') {
            return 0;
        }
        if ($this->maxTotalUploadSize() == 0) {
            return 100;
        }

        return round((($this->totalUploadSize() / $this->maxTotalUploadSize()) * 100), 2);
    }

    /**
     * Custom can for customer.
     *
     * @return bool
     */
    public function can($action, $item)
    {
        return \Auth::user()->can($action, [$item, 'customer']);
    }

    /**
     * Email verification servers count.
     *
     * @var int
     */
    public function emailVerificationServersCount()
    {
        return $this->emailVerificationServers()->count();
    }

    /**
     * Get list of available email verification servers.
     *
     * @var bool
     */
    public function getEmailVerificationServers()
    {
        if (!config('app.saas')) {
            return EmailVerificationServer::getAllAdminActive()->get()->map(function ($server) {
                return $server;
            });
        } elseif ($this->getCurrentActiveGeneralSubscription()->planGeneral->useOwnEmailVerificationServer()) {
            return $this->activeEmailVerificationServers()->get()->map(function ($server) {
                return $server;
            });
            // If customer dont have permission creating sending servers
        } else {
            // Get server from the plan
            return  $this->getCurrentActiveGeneralSubscription()->planGeneral->getEmailVerificationServers();
        }
    }

    /**
     * Get email verification servers select options.
     *
     * @return array
     */
    public function emailVerificationServerSelectOptions()
    {
        $servers = $this->getEmailVerificationServers();
        $options = [];
        foreach ($servers as $server) {
            $options[] = ['text' => $server->name, 'value' => $server->uid];
        }

        return $options;
    }

    /**
     * Get customer's sending servers type.
     *
     * @return array
     */
    public function getSendingServertypes()
    {
        $allTypes = \Acelle\Model\SendingServer::types();
        $types = [];

        foreach ($allTypes as $type => $server) {
            if ($this->isAllowCreateSendingServerType($type)) {
                $types[$type] = $server;
            }
        }

        if (!Setting::isYes('delivery.sendmail')) {
            unset($types['sendmail']);
        }

        if (!Setting::isYes('delivery.phpmail')) {
            unset($types['php-mail']);
        }

        return $types;
    }

    /**
     * Check customer can create sending servers type.
     *
     * @return bool
     */
    public function isAllowCreateSendingServerType($type)
    {
        $customerTypes = get_tmp_quota($this, 'sending_server_types');
        if (get_tmp_quota($this, 'all_sending_server_types') == 'yes' ||
            (isset($customerTypes[$type]) && $customerTypes[$type] == 'yes')
        ) {
            return true;
        }

        return false;
    }

    /**
     * Add email to customer's blacklist.
     */
    public function addEmaillToBlacklist($email)
    {
        $email = trim(strtolower($email));

        if (\Acelle\Library\Tool::isValidEmail($email)) {
            $exist = $this->blacklists()->where('email', '=', $email)->count();
            if (!$exist) {
                $blacklist = new \Acelle\Model\Blacklist();
                $blacklist->customer_id = $this->id;
                $blacklist->email = $email;
                $blacklist->save();
            }
        }
    }

    /**
     * Check if customer has api access.
     *
     * @return bool
     */
    public function canUseApi()
    {
        return get_tmp_quota($this, 'api_access') == 'yes';
    }

    /**
     * Get all verified identities.
     *
     * @return array
     */
    public function verifiedIdentitiesDroplist($keyword = null)
    {
        $droplist = [];
        $topList = [];
        $bottomList = [];

        if (!$keyword) {
            $keyword = '###';
        }

        foreach ($this->getVerifiedIdentities() as $item) {
            // check if email
            if (extract_email($item) !== null) {
                $email = extract_email($item);
                if (strpos(strtolower($email), $keyword) === 0) {
                    $topList[] = [
                        'text' => extract_name($item),
                        'value' => $email,
                        'desc' => str_replace($keyword, '<span class="text-semibold text-primary"><strong>'.$keyword.'</strong></span>', $email),
                    ];
                } else {
                    $bottomList[] = [
                        'text' => extract_name($item),
                        'value' => $email,
                        'desc' => $email,
                    ];
                }
            } else { // domains are alse
                $dKey = explode('@', $keyword);
                $eKey = $dKey[0];
                $dKey = isset($dKey[1]) ? $dKey[1] : null;
                // if ( (!isset($dKey) || $dKey == '') || ($dKey && strpos(strtolower($item), $dKey) === 0 )) {
                if ($keyword == '###') {
                    $eKey = '****';
                }
                $topList[] = [
                        'text' => $eKey.'@'.str_replace($dKey, '<span class="text-semibold text-primary"><strong>'.$dKey.'</strong></span>', $item),
                        'subfix' => $item,
                        'desc' => null,
                    ];
                // }
            }
        }

        $droplist = array_merge($topList, $bottomList);

        return $droplist;
    }

    public function getLockPath($path)
    {
        return $this->getFirstUserLegacy()->getLockPath($path);
    }

    public function allowUnverifiedFromEmailAddress()
    {
        if (config('custom.sign_with_default_domain')) {
            // cho phép FROM EMAIL nào cũng được
            return true;
        }


        $server = null;
        if (!config('app.saas')) {
            $server = get_tmp_primary_server();
        } else {
            $plan = $this->getNewOrActiveGeneralSubscription()->planGeneral;

            if ($plan->useSystemSendingServer()) {
                $server = $plan->primarySendingServer();
            }
        }

        return (is_null($server)) ? true : $server->allowUnverifiedFromEmailAddress();
    }

    // Get the current time in Customer timezone
    public function getCurrentTime()
    {
        return Carbon::now($this->timezone);
    }

    public function parseDateTime($datetime, $fallback = false)
    {
        // IMPORTANT: datetime string must NOT contain timezone information
        // IMPORTANT: passing [$this->timezone] is critically needed, to make sure this function works in console
        try {
            $dt = Carbon::parse($datetime, $this->timezone);
            $dt = $dt->timezone($this->timezone);
        } catch (\Exception $ex) {
            // parse special chars, for example ('sadfh&#($783943') ==> exception
            if ($fallback) {
                $dt = $this->parseDateTime('1900-01-01');
            } else {
                throw $ex;
            }
        }
        return $dt;
    }

    public function formatCurrentDateTime($name)
    {
        return $this->formatDateTime($this->getCurrentTime(), $name);
    }

    public function formatDateTime(Carbon $datetime, string $name)
    {
        // $name is a format name like: date_full | date_short
        // See config('localization')['*'] for the full list of available format names
        return format_datetime($datetime->timezone($this->getTimezone()), $name, $this->getLanguageCode());
    }

    public function getPreferredPaymentGateway()
    {
        // Value of "payment_method" was every time user chooses a payment method in the web UI
        // See SubscriptionController@checkout() function where it is set
        if (!$this['payment_method']) {
            return null;
        }

        // For example, the user preferred method is "paddle", but paddle is no longer available
        // Then simply return null
        // So, in the web UI, this user does not have a preferred payment method
        $meta = json_decode($this['payment_method'], true);

        if (!array_key_exists('method', $meta)) {
            throw new Exception("The 'method' key is required for 'preferred payment' data");
        }

        $type = $meta['method'];

        if ($type && Billing::isGatewayRegistered($type)) {
            return Billing::getGateway($type);
        } else {
            // payment method was previously stored but it is dropped in the current version
            return null;
        }
    }

    /**
     * Update payment method.
     *
     * @var object | collect
     */
    public function updatePaymentMethod($data = [])
    {
        // $paymentMethod = $this->getPaymentMethod();

        // if (!isset($paymentMethod)) {
        //     $paymentMethod = [];
        // }

        // $data = (object) array_merge((array) $paymentMethod, $data);
        $this['payment_method'] = json_encode($data);

        $this->save();
    }

    /**
     * Remove payment method.
     *
     * @var object | collect
     */
    public function removePaymentMethod()
    {
        $this->payment_method = null;
        $this->save();
    }

    public function getAutoBillingData()
    {
        if ($this->auto_billing_data == null) {
            return null;
        }

        return AutoBillingData::fromJson($this->auto_billing_data);
    }

    public function setAutoBillingData(AutoBillingData $autoBillingData)
    {
        $this->auto_billing_data = $autoBillingData->toJson();
        $this->save();
    }

    // Do not call delete() directly on Customer
    // This method helps delete Customer account but KEEP
    // the associated User account if it is also associated with an Admin
    public function deleteAccount()
    {
        DB::transaction(function () {
            $this->delete();
        });
    }

    public function copyTemplateAs(Template $template, $name)
    {
        $copy = $template->copy([
            'name' => $name,
            'customer_id' => $this->id
        ]);

        return $copy;
    }

    /**
     * Get builder templates.
     *
     * @return mixed
     */
    public function getBuilderTemplates()
    {
        $result = [];

        // Gallery
        $templates = \Acelle\Model\Template::where('customer_id', '=', $this->id)
            ->orderBy('customer_id')
            ->notPreserved()
            ->get();

        foreach ($templates as $template) {
            $result[] = [
                'name' => $template->name,
                'url' => '', // action('CampaignController@templateChangeTemplate', ['uid' => $this->uid, 'template_uid' => $template->uid]),
                'thumbnail' => $template->getThumbUrl(),
            ];
        }

        return $result;
    }

    public function preferredPaymentGatewayCanAutoCharge()
    {
        // check auto billing data
        if ($this->getPreferredPaymentGateway() == null) {
            return false;
        }

        // support auto billing
        if (!$this->getPreferredPaymentGateway()->supportsAutoBilling()) {
            return false;
        }

        // no autobilling data
        if ($this->getAutoBillingData() == null || $this->getAutoBillingData()->getGateway() == null) {
            return false;
        }

        // check payment
        if ($this->getPreferredPaymentGateway()->getType() != $this->getAutoBillingData()->getGateway()->getType()) {
            return false;
        }

        return true;
    }

    public function importBlacklistJobs()
    {
        return $this->jobMonitors()->orderBy('job_monitors.id', 'DESC')->where('job_type', ImportBlacklistJob::class);
    }

    public function getMenuLayout()
    {
        return ($this->menu_layout == 'left' ? 'left' : 'top');
    }

    public function allowVerifyingOwnDomains()
    {
        if (config('app.saas')) {
            $subscription = $this->getCurrentActiveGeneralSubscription();
            $plan = $subscription->planGeneral;
            if ($plan->useSystemSendingServer()) {
                $server = $plan->primarySendingServer();
            } else {
                return true;
            }
        } else {
            $server = get_tmp_primary_server();
        }

        if ($server && ($server->allowVerifyingOwnDomains() || $server->allowVerifyingOwnDomainsRemotely())) {
            return true;
        } else {
            return false;
        }
    }

    public static function newCustomer()
    {
        $customer = new self();
        $customer->menu_layout = \Acelle\Model\Setting::get('layout.menu_bar');
        $customer->status = self::STATUS_ACTIVE;

        return $customer;
    }

    public static function scopeByPlan($query, $plan)
    {
        $query = $query->whereHas('subscriptions', function ($q) use ($plan) {
            $q->newOrActive()->where('plan_id', '=', $plan->id);
        });
    }

    public function displayName()
    {
        return $this->name;
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

    /*
     *
     * FUNCTIONS THAT ARE DEPENDENT ON PLAN/SUBSCRIPTIONS
     *
     */

    public function getCurrentActiveGeneralSubscription()
    {
        if (!config('app.saas')) {
            throw new Exception('Operation not allowed in NON-SAAS mode');
        }

        // only ONE
        $subscriptions = $this->generalSubscriptions()->active();

        if ($subscriptions->count() > 1) {
            throw new Exception('There are 2 subscriptions of [active] status. Please clean up the DB');
        }

        return $subscriptions->first();
    }

    public function isCustomTrackingDomainRequired()
    {
        if (!config('app.saas')) {
            return false;
        }

        $plan = $this->getNewOrActiveGeneralSubscription()->planGeneral;

        if (!$plan) {
            return false;
        }

        return $plan->isCustomTrackingDomainRequired();
    }

    // Customer has only ONE general subscription which is NEW or ACTIVE
    public function getNewOrActiveGeneralSubscription()
    {
        $subscriptions = $this->generalSubscriptions()->newOrActive();

        if ($subscriptions->count() > 1) {
            throw new Exception('There are 2 subscriptions of [active, new] status. Please clean up the DB');
        }

        return $subscriptions->first();
    }

    // MISS FIRST primary sending server's identities here
    public function getVerifiedIdentities()
    {
        $list = [];

        // own emails
        if (!config('app.saas') || $this->getCurrentActiveGeneralSubscription()->planGeneral->allowSenderVerification()) {
            $senders = $this->local()->senders()->verified()->get();
            foreach ($senders as $sender) {
                $list[] = $sender->name.' <'.$sender->email.'>';
            }
        }

        // own domain
        $domains = $this->local()->sendingDomains()->active();

        if (config('app.saas')) {
            $plan = $this->getCurrentActiveGeneralSubscription()->planGeneral;
            if ($plan->useSystemSendingServer()) {
                $server = $plan->primarySendingServer();
                if (!$server->allowOtherSendingDomains()) {
                    $domains->bySendingServer($server);
                }
            }
        }

        foreach ($domains->get() as $domain) {
            $list[] = $domain->name;
        }

        if (config('app.saas')) {
            // plan sending server emails if system sending servers
            if ($plan->useSystemSendingServer()) {
                $list = array_merge($plan->getVerifiedIdentities(), $list);
            }
        }

        return array_values(array_unique($list));
    }

    public function assignGeneralPlan($plan)
    {
        // Put in transaction. Make sure senderID always with subscription
        $subscription = \DB::transaction(function () use ($plan) {
            if ($this->getNewOrActiveGeneralSubscription()) {
                throw new \Exception(trans('messages.customer.already_new_active_sub'));
            }

            // allways create init subscription with init invoice (by design)
            $subscription = Subscription::createNewSubscription($this, $plan);

            if ($plan->hasTrial()) {
                $invoice = $subscription->createNewSubscriptionInvoiceWithTrial();
            } else {
                $invoice = $subscription->createNewSubscriptionInvoiceWithoutTrial();
            }

            // Assign sending credits
            // Notice that the following method is available for a SubscriptionGeneralPlan only
            $subscription->setDefaultEmailCredits();
            $subscription->setDefaultVerificationCredits();

            // log
            SubscriptionFacade::log($subscription, SubscriptionLog::TYPE_SELECT_PLAN, $invoice->uid, [
                'plan' => $subscription->getPlanName(),
                'customer' => $subscription->getCustomerName(),
                'amount' => $invoice->total(),
            ]);

            return $subscription;
        });

        return $subscription;
    }

    /**
     * Check customer if has notice.
     */
    public function hasSubscriptionNotice()
    {
        if (!config('app.saas')) {
            return false;
        }

        $subscription = $this->getNewOrActiveGeneralSubscription();

        if (is_null($subscription)) {
            return false;
        }


        if ($subscription->getUnpaidInvoice()) {
            return true;
        }

        return false;
    }

    public function createEmailVerificarionCreditsInvoice($amount, $currency, $credits)
    {
        // create init invoice
        $invoice = \Acelle\Model\Invoice::createInvoice(
            $type = \Acelle\Model\InvoiceEmailVerificationCredits::TYPE_EMAIL_VERIFICATION_CREDITS,
            $title = trans('messages.invoice.email_verification_credits', [
                'amount' => number_with_delimiter($credits),
            ]),
            $description = trans('messages.invoice.email_verification_credits.desc', [
                'amount' => number_with_delimiter($credits),
            ]),
            $customer_id = $this->id,
            $currency_id = $currency->id,
            $billing_address = $this->getDefaultBillingAddress(),
            $invoiceItems = [
                new InvoiceItem([
                    'item_id' => $this->id,
                    'item_type' => get_class($this),
                    'amount' => $amount,
                    'title' => trans('messages.invoice.email_verification_credits', [
                        'amount' => number_with_delimiter($credits),
                    ]),
                    'description' => trans('messages.invoice.email_verification_credits.desc', [
                        'amount' => number_with_delimiter($credits),
                    ]),
                ]),
            ],
        );

        // save top up info to invoice
        $invoice->email_verification_credits = $credits;
        $invoice->save();

        // return
        return $invoice;
    }

    public function emailVerificationCreditsInvoices()
    {
        $id = $this->id;
        $type = self::class;
        return $this->invoices()
            ->emailVerificationCredits()
            ->whereIn('id', function ($query) use ($id, $type) {
                $query->select('invoice_id')
                    ->from(with(new InvoiceItem())->getTable())
                        ->where('item_type', $type)
                        ->where('item_id', $id);
            });
    }

    public function emailVerificationCreditsTransactions()
    {
        return $this->transactions()->whereIn('invoice_id', $this->emailVerificationCreditsInvoices()->select('id'));
    }

    public function getLastPaidEmailVerificationCreditsInvoice()
    {
        return $this->emailVerificationCreditsInvoices()
            ->paid()
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    public function createSendingCreditsInvoice($amount, $currency, $credits)
    {
        // create init invoice
        $invoice = \Acelle\Model\Invoice::createInvoice(
            $type = \Acelle\Model\InvoiceSendingCredits::TYPE_SENDING_CREDITS,
            $title = trans('messages.invoice.sending_credit', [
                'amount' => number_with_delimiter($credits),
            ]),
            $description = trans('messages.invoice.sending_credit.desc', [
                'amount' => number_with_delimiter($credits),
            ]),
            $customer_id = $this->id,
            $currency_id = $currency->id,
            $billing_address = $this->getDefaultBillingAddress(),
            $invoiceItems = [
                new InvoiceItem([
                    'item_id' => $this->id,
                    'item_type' => get_class($this),
                    'amount' => $amount,
                    'title' => trans('messages.invoice.sending_credit', [
                        'amount' => number_with_delimiter($credits),
                    ]),
                    'description' => trans('messages.invoice.sending_credit.desc', [
                        'amount' => number_with_delimiter($credits),
                    ]),
                ]),
            ],
        );

        // save top up info to invoice
        $invoice->sending_credits = $credits;
        $invoice->save();

        // return
        return $invoice;
    }

    public function sendingCreditsInvoices()
    {
        $id = $this->id;
        $type = self::class;
        return $this->invoices()
            ->sendingCredits()
            ->whereIn('id', function ($query) use ($id, $type) {
                $query->select('invoice_id')
                    ->from(with(new InvoiceItem())->getTable())
                        ->where('item_type', $type)
                        ->where('item_id', $id);
            });
    }

    public function sendingCreditsTransactions()
    {
        return $this->transactions()->whereIn('invoice_id', $this->sendingCreditsInvoices()->select('id'));
    }

    public function getSendingCredits()
    {
        return $this->sendingCreditsInvoices()->paid()->sum('sending_credits');
    }

    public function getLastPaidSendingCreditsInvoice()
    {
        return $this->sendingCreditsInvoices()
            ->paid()
            ->orderBy('created_at', 'DESC')
            ->first();
    }

    public static function findOrCreateCustomerByEmail($email)
    {
        $user = User::where('email', $email)->first();

        // Create new customer
        if (!$user) {
            // Create new customer
            $customer = self::newCustomer();
            $customer->timezone = config('app.timezone');
            $customer->language_id = Language::getDefaultLanguage()->id;
            $customer->save();

            $user = $customer->newUser();
            $user->email = $email;
            $user->activated = true;
            $user->customer_id = $customer->id;
            $user->password = bcrypt('12345678');

            $user->save();
        } else {
            $customer = $user->customer;
        }

        return [$customer, $user];
    }

    public function newUser()
    {
        $user = User::newDefault();
        $user->customer_id = $this->id;
        return $user;
    }

    public function saveChatgptApiKey($key)
    {
        $this->openai_api_key = $key;
        $this->save();
    }

    public function getChatgptApiKey()
    {
        return $this->openai_api_key;
    }

    public function validateFromRequest($request)
    {
        // validation
        $validator = \Validator::make($request->all(), [
            'name' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
        ]);

        // errors
        if ($validator->fails()) {
            return $validator;
        }

        return $validator;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getGoogleAccessToken()
    {
        if (!$this->google_auth) {
            return null;
        }
        return json_decode($this->google_auth, true)['access_token'];
    }

    public function checkAddingSubscribersToMailListReachLimit($mailList, $addNumber)
    {
        $max = get_tmp_quota($this, 'subscriber_max');
        $maxPerList = get_tmp_quota($this, 'subscriber_per_list_max');
        return $this->id == $mailList->customer_id &&
            ($max >= $this->subscribersCount() + $addNumber || $max == -1) &&
            ($maxPerList >= $mailList->subscribersCount() + $addNumber || $maxPerList == -1);
    }

    public static function createCustomer(
        $name,
        $timezone,
        $language_id,
        $admin = null
    ) {
        // init
        $customer = static::newCustomer();

        // fill customer data
        $customer->name = $name;
        $customer->timezone = $timezone;
        $customer->language_id = $language_id;

        // rules
        $rules = [
            'name' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
        ];

        // validation
        $validator = \Validator::make([
            'name' => $name,
            'timezone' => $timezone,
            'language_id' => $language_id,
        ], $rules);

        // errors
        if ($validator->fails()) {
            return [$validator, $customer];
        }

        // SAVE CUSTOMER
        if ($admin) {
            $customer->admin_id = $admin->id;
        }
        $customer->save();

        // Execute registered hooks
        Hook::execute('customer_added', [$customer]);

        // success
        return [$validator, $customer];
    }

    public function updateCustomer(
        $name,
        $timezone,
        $language_id
    ) {
        // fill customer data
        $this->name = $name;
        $this->timezone = $timezone;
        $this->language_id = $language_id;

        // rules
        $rules = [
            'name' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
        ];

        // validation
        $validator = \Validator::make([
            'name' => $name,
            'timezone' => $timezone,
            'language_id' => $language_id,
        ], $rules);

        // errors
        if ($validator->fails()) {
            return [$validator, $this];
        }

        // SAVE CUSTOMER
        $this->save();

        // success
        return [$validator, $this];
    }

    public static function createCustomerWithDefaultUser(
        $admin,
        $name,
        $timezone,
        $language_id,
        $email,
        $password,
        $passwordConfirmation,
        $first_name,
        $last_name,
        $image,
        $role_uid,
        $phone = null,
    ) {
        // init
        $customer = static::newCustomer();
        $user = $customer->newUser();

        // fill customer data
        $customer->name = $name;
        $customer->timezone = $timezone;
        $customer->language_id = $language_id;

        // fill user data
        $user->email = $email;
        $user->password = $password;
        $user->first_name = $first_name;
        $user->last_name = $last_name;
        $user->phone = $phone;

        // rules
        $rules = [
            'name' => 'required',
            'timezone' => 'required',
            'language_id' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:5|max:255',
            'first_name' => 'required',
            'last_name' => 'required',
            'image' => 'nullable|image',
            'role_uid' => 'required',
            'phone' => [(Setting::get('user.require_mobile_phone') == 'yes' ? 'required' : 'nullable'), 'regex:/^[\+0-9]{10,15}$/'],
        ];

        // validation
        $validator = \Validator::make([
            'name' => $name,
            'timezone' => $timezone,
            'language_id' => $language_id,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'image' => $image,
            'role_uid' => $role_uid,
            'phone' => $phone,
        ], $rules);

        // errors
        if ($validator->fails()) {
            return [$validator, $customer, $user];
        }

        // SAVE USER
        list($result, $errors) = $user->saveUser(
            $email,
            $password,
            $passwordConfirmation,
            $first_name,
            $last_name,
            $image,
            $role_uid,
            $phone
        );

        // SAVE CUSTOMER
        list($validator, $customer) = static::createCustomer(
            $name,
            $timezone,
            $language_id,
            $admin
        );

        // set customer
        $user->customer_id = $customer->id;
        $user->save();

        // success
        return [$validator, $customer, $user];
    }

    public function hasLocalDb()
    {
        return !is_null($this->db_connection);
    }

    public function local()
    {
        if (config('sharding.enabled') && $this->hasLocalDb()) {
            $local = LocalCustomer::on($this->db_connection)->where('uid', $this->uid)->first();

            if (is_null($local)) {
                throw new Exception("Cannot find a local customer with UID '{$this->uid}' in '{$this->db_connection}' connection. Execute the 'createLocalInstance()' function to create one");
            }

            return $local;
        } else {

            // In case of NO SHARDING, simply return the same customer in the same connection, typecast it to a LocalCustomer;
            $local = LocalCustomer::on('mysql')->where('uid', $this->uid)->first();
            return $local;
        }
    }

    public function createLocalInstance()
    {
        return LocalCustomer::sync($this);
    }

    public function newMailList()
    {
        $list = new MailList(['all_sending_servers' => true]);
        $list->customer_id = $this->id;

        return $list;
    }

    public function getMaxUserQuota()
    {
        if (!$this->getCurrentActiveSubscription()) {
            return 0;
        }

        return $this->getCurrentActiveSubscription()->plan->mapType()->getOption('user_max');
    }

    public function setUserDbConnection()
    {
        if (config('sharding.enabled')) {
            if ($this->hasLocalDb()) {
                \Acelle\Helpers\set_userdb_connection($this->db_connection);
            }
        }
    }

    public function ownOrGlobalRoles()
    {
        return $this->roles()
            ->orWhere(function ($q) {
                $q->global();
            });
    }

    public function getSelectableRoles()
    {
        return $this->ownOrGlobalRoles()->get();
    }

    public static function mapKeyToCustomer($namespace, $key, $customerId)
    {
        $fullkey = static::getFullKey($namespace, $key);
        cache([$fullkey => $customerId], now()->addMonths(2));
    }

    // @tricky: if cannot find a mapping in cache, then try to extract from the key itself
    public static function getCustomerFromKey($namespace, $key)
    {
        $fullkey = static::getFullKey($namespace, $key);
        $customerId = cache($fullkey);
        $customer = static::find($customerId);

        // @trick here!
        if (is_null($customer)) {
            $customerUid = \Acelle\Library\StringHelper::extractCustomerUidFromMessageId($key);

            if (!is_null($customerUid)) {
                $customer = \Acelle\Model\Customer::findByUid($customerUid);
            }
        }

        return $customer;
    }

    private static function getFullKey($namespace, $key)
    {
        $connector = '_';
        $fullkey = "{$namespace}{$connector}{$key}";
        return $fullkey;
    }

    public function getDbConnection()
    {
        return $this->db_connection ?? 'mysql';
    }
}
