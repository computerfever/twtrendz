<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log as LaravelLog;
use Acelle\Library\Facades\Billing;

class AccountController extends Controller
{
    /**
     * Update user profile.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function profile(Request $request)
    {
        // Get current user
        $user = $request->user();
        $customer = $user->customer;
        $customer->getColorScheme();

        // Save posted data
        if ($request->isMethod('post')) {
            $this->validate($request, [
                'first_name' => 'required',
                'last_name' => 'required',
                'timezone' => 'required',
                'language_id' => 'required',
                'image' => 'nullable|image',
                'phone' => [(\Acelle\Model\Setting::get('user.require_mobile_phone') == 'yes' ? 'required' : 'nullable'), 'regex:/^[\+0-9]{10,15}$/'],
            ]);

            // Update user attributes
            $user->first_name = $request->first_name;
            $user->last_name = $request->last_name;
            $user->phone = $request->phone;

            // Update password
            if (!empty($request->password)) {
                $user->password = bcrypt($request->password);
            }

            $user->save();

            // Authorize
            if ($request->user()->customer->can('updateProfile', $customer)) {
                // Save current user info
                $customer->fill($request->all());

                // Upload and save image
                if ($request->hasFile('image')) {
                    if ($request->file('image')->isValid()) {
                        // Remove old images
                        $user->uploadProfileImage($request->file('image'));
                    }
                }

                // Remove image
                if ($request->_remove_image == 'true') {
                    $user->removeProfileImage();
                }

                if ($customer->save()) {
                    $request->session()->flash('alert-success', trans('messages.profile.updated'));
                }
            }

            return redirect()->action('AccountController@profile');
        }

        if (!empty($request->old())) {
            $customer->fill($request->old());
            // User info
            $user->fill($request->old());
        }

        return view('account.profile', [
            'customer' => $customer,
            'user' => $request->user(),
        ]);
    }

    /**
     * Update customer contact information.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function contact(Request $request)
    {
        // Get current user
        $customer = $request->user()->customer;
        $contact = $customer->contact;

        // Create new company if null
        if (!$contact) {
            $contact = new \Acelle\Model\Contact();
        }

        // save posted data
        if ($request->isMethod('post')) {
            // Prenvent save from demo mod
            if (config('app.demo')) {
                return view('somethingWentWrong', ['message' => trans('messages.operation_not_allowed_in_demo')]);
            }

            $this->validate($request, \Acelle\Model\Contact::$rules);

            $contact->fill($request->all());

            // Save current user info
            if ($contact->save()) {
                $customer->contact_id = $contact->id;
                $customer->save();
                $request->session()->flash('alert-success', trans('messages.customer_contact.updated'));
            }
        }

        return view('account.contact', [
            'customer' => $customer,
            'contact' => $contact->fill($request->old()),
        ]);
    }

    /**
     * User logs.
     */
    public function logs(Request $request)
    {
        $logs = $request->user()->customer->logs;

        return view('account.logs', [
            'logs' => $logs,
        ]);
    }

    /**
     * Logs list.
     */
    public function logsListing(Request $request)
    {
        $logs = \Acelle\Model\Log::search($request)->paginate($request->per_page);

        return view('account.logs_listing', [
            'logs' => $logs,
        ]);
    }

    /**
     * Quta logs.
     */
    public function quotaLog(Request $request)
    {
        $customer = $request->user()->customer;
        if (config('app.saas')) {
            $subscription = $customer->getCurrentActiveGeneralSubscription(); // saas safe
            $sendCreditTracker = $subscription->getSendEmailCreditTracker();

            $sendCreditRemaining = $sendCreditTracker->getRemainingCredits();
            $sendingCreditsLimit = $subscription->getCreditsLimit('send');
        } else {
            $remainingEmailCredits = -1;
            $emailCredits = -1;
            $remainingEmailCreditsPercentage = -1;
        }

        $maxLists = get_tmp_quota($customer, 'list_max');
        $maxCampaigns = get_tmp_quota($customer, 'campaign_max');
        $maxSubscribers = get_tmp_quota($customer, 'subscriber_max');
        $maxAutomations = get_tmp_quota($customer, 'automation_max');
        $maxUpload = get_tmp_quota($customer, 'max_size_upload_total');
        $maxDomains = get_tmp_quota($customer, 'sending_domains_max');

        $listsCount = $customer->local()->listsCount();
        $listsUsed = ($maxLists == -1) ? 0 : (($maxLists == 0) ? 1 : $listsCount / $maxLists);

        $campaignsCount = $customer->local()->campaignsCount();
        $campaignsUsed = ($maxCampaigns == -1) ? 0 : (($maxCampaigns == 0) ? 1 : $campaignsCount / $maxCampaigns);

        $subscribersCount = $customer->local()->readCache('SubscriberCount', 0);
        $subscribersUsed = ($maxSubscribers == -1) ? 0 : (($maxSubscribers == 0) ? 1 : $subscribersCount / $maxSubscribers);

        $automationsCount = $customer->local()->automationsCount();
        $automationsUsed = ($maxAutomations == -1) ? 0 : (($maxAutomations == 0) ? 1 : $automationsCount / $maxAutomations);

        $uploadCount = $customer->totalUploadSize();
        $uploadUsed = ($maxUpload == -1) ? 0 : (($maxUpload == 0) ? 1 : $uploadCount / $maxUpload);

        $domainsCount = $customer->local()->sendingDomainsCount();
        $domainsUsed = ($maxDomains == -1) ? 0 : (($maxDomains == 0) ? 1 : $domainsCount / $maxDomains);

        // users
        $usersCount = $customer->users()->count();
        $maxUsers = $customer->getMaxUserQuota();
        $usersUsed = ($maxUsers == -1) ? 0 : (($maxUsers == 0) ? 1 : $usersCount / $maxUsers);

        return view('account.quota_log', [
            'sendCreditRemaining' => $sendCreditRemaining,
            'sendingCreditsLimit' => $sendingCreditsLimit,
            'maxLists' => $maxLists,
            'listsCount' => $listsCount,
            'listsUsed' => $listsUsed,
            'maxCampaigns' => $maxCampaigns,
            'campaignsCount' => $campaignsCount,
            'campaignsUsed' => $campaignsUsed,
            'maxSubscribers' => $maxSubscribers,
            'subscribersCount' => $subscribersCount,
            'subscribersUsed' => $subscribersUsed,
            'maxAutomations' => $maxAutomations,
            'automationsCount' => $automationsCount,
            'automationsUsed' => $automationsUsed,
            'maxUpload' => $maxUpload,
            'uploadCount' => $uploadCount,
            'uploadUsed' => $uploadUsed,
            'maxDomains' => $maxDomains,
            'domainsCount' => $domainsCount,
            'domainsUsed' => $domainsUsed,

            'maxUsers' => $maxUsers,
            'usersCount' => $usersCount,
            'usersUsed' => $usersUsed,
        ]);
    }

    /**
     * Quta logs 2.
     */
    public function quotaLog2(Request $request)
    {
        $customer = $request->user()->customer;
        if (config('app.saas')) {
            $subscription = $customer->getCurrentActiveGeneralSubscription(); // saas safe
            $sendCreditTracker = $subscription->getSendEmailCreditTracker();
            $sendingCreditsLimit = $subscription->getCreditsLimit('send');
        } else {
            $remainingEmailCredits = -1;
            $emailCredits = -1;
            $remainingEmailCreditsPercentage = -1;
        }

        $maxLists = get_tmp_quota($customer, 'list_max');
        $maxCampaigns = get_tmp_quota($customer, 'campaign_max');
        $maxSubscribers = get_tmp_quota($customer, 'subscriber_max');
        $maxAutomations = get_tmp_quota($customer, 'automation_max');
        $maxUpload = get_tmp_quota($customer, 'max_size_upload_total');
        $maxDomains = get_tmp_quota($customer, 'sending_domains_max');

        $listsCount = $customer->local()->listsCount();
        $listsUsed = ($maxLists == -1) ? 0 : (($maxLists == 0) ? 1 : $listsCount / $maxLists);

        $campaignsCount = $customer->local()->campaignsCount();
        $campaignsUsed = ($maxCampaigns == -1) ? 0 : (($maxCampaigns == 0) ? 1 : $campaignsCount / $maxCampaigns);

        $subscribersCount = $customer->local()->readCache('SubscriberCount', 0);
        $subscribersUsed = ($maxSubscribers == -1) ? 0 : (($maxSubscribers == 0) ? 1 : $subscribersCount / $maxSubscribers);

        $automationsCount = $customer->local()->automationsCount();
        $automationsUsed = ($maxAutomations == -1) ? 0 : (($maxAutomations == 0) ? 1 : $automationsCount / $maxAutomations);

        $uploadCount = $customer->totalUploadSize();
        $uploadUsed = ($maxUpload == -1) ? 0 : (($maxUpload == 0) ? 1 : $uploadCount / $maxUpload);

        $domainsCount = $customer->local()->sendingDomainsCount();
        $domainsUsed = ($maxDomains == -1) ? 0 : (($maxDomains == 0) ? 1 : $domainsCount / $maxDomains);

        return view('account.quota_log_2', [
            'sendingCreditsLimit' => $sendingCreditsLimit,
            'maxLists' => $maxLists,
            'listsCount' => $listsCount,
            'listsUsed' => $listsUsed,
            'maxCampaigns' => $maxCampaigns,
            'campaignsCount' => $campaignsCount,
            'campaignsUsed' => $campaignsUsed,
            'maxSubscribers' => $maxSubscribers,
            'subscribersCount' => $subscribersCount,
            'subscribersUsed' => $subscribersUsed,
            'maxAutomations' => $maxAutomations,
            'automationsCount' => $automationsCount,
            'automationsUsed' => $automationsUsed,
            'maxUpload' => $maxUpload,
            'uploadCount' => $uploadCount,
            'uploadUsed' => $uploadUsed,
            'maxDomains' => $maxDomains,
            'domainsCount' => $domainsCount,
            'domainsUsed' => $domainsUsed,
        ]);
    }

    /**
     * Api token.
     */
    public function api(Request $request)
    {
        return view('account.api');
    }

    /**
     * Renew api token.
     */
    public function renewToken(Request $request)
    {
        $user = $request->user();

        $user->api_token = str_random(60);
        $user->save();

        // Redirect to my lists page
        $request->session()->flash('alert-success', trans('messages.user_api.renewed'));

        return redirect()->action('AccountController@api');
    }

    public function openPortal(Request $request){

        $gateway = Billing::getGateway('stripe');

        $autoBillingData = $request->user()->customer->getAutoBillingData()->getData();

        $stripe = new \Stripe\StripeClient($gateway->getSecretKey());

        $session = $stripe->billingPortal->sessions->create([
          'customer' => $autoBillingData['customer_id'],
          'return_url' => action("AccountController@billing"),
        ]);

        return redirect()->away($session->url);
    }

    /**
     * Billing.
     */
    public function billing(Request $request)
    {
        return view('account.billing', [
            'customer' => $request->user()->customer,
            'user' => $request->user(),
        ]);
    }

    /**
     * Edit billing address.
     */
    public function editBillingAddress(Request $request)
    {
        $customer = $request->user()->customer;
        $billingAddress = $customer->getDefaultBillingAddress();

        // has no address yet
        if (!$billingAddress) {
            $billingAddress = $customer->newBillingAddress();
        }

        // copy from contacy
        if ($request->same_as_contact == 'true') {
            $billingAddress->copyFromContact();
        }

        // Save posted data
        if ($request->isMethod('post')) {
            list($validator, $billingAddress) = $billingAddress->updateAll($request);

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('account.editBillingAddress', [
                    'billingAddress' => $billingAddress,
                    'errors' => $validator->errors(),
                ], 400);
            }

            $request->session()->flash('alert-success', trans('messages.billing_address.updated'));

            return;
        }

        return view('account.editBillingAddress', [
            'billingAddress' => $billingAddress,
        ]);
    }

    /**
     * Remove payment method
     */
    public function removePaymentMethod(Request $request)
    {
        $customer = $request->user()->customer;

        $customer->removePaymentMethod();
    }

    /**
     * Edit payment method
     */
    public function editPaymentMethod(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        // Save posted data
        if ($request->isMethod('post')) {
            if (!Billing::isGatewayRegistered($request->payment_method)) {
                throw new \Exception('Gateway for ' . $request->payment_method . ' is not registered!');
            }

            $gateway = Billing::getGateway($request->payment_method);

            $request->user()->customer->updatePaymentMethod([
                'method' => $request->payment_method,
            ]);

            if ($gateway->supportsAutoBilling()) {
                return redirect()->away($gateway->getAutoBillingDataUpdateUrl($request->return_url));
            }

            return redirect()->away($request->return_url);
        }

        return view('account.editPaymentMethod', [
            'redirect' => $request->redirect ? $request->redirect : action('AccountController@billing'),
        ]);
    }

    public function leftbarState(Request $request)
    {
        $request->session()->put('customer-leftbar-state', $request->state);
    }

    public function wizardColorScheme(Request $request)
    {
        $customer = $request->user()->customer;

        // Save color scheme
        if ($request->isMethod('post')) {
            $customer->color_scheme = $request->color_scheme;
            $customer->theme_mode = $request->theme_mode;
            $customer->save();

            return view('account.wizardMenuLayout');
        }

        return view('account.wizardColorScheme');
    }

    public function wizardMenuLayout(Request $request)
    {
        $customer = $request->user()->customer;

        // Save color scheme
        if ($request->isMethod('post')) {
            $customer->menu_layout = $request->menu_layout;
            $customer->save();
            return;
        }

        return view('account.wizardMenuLayout');
    }

    public function activity(Request $request)
    {
        $currentTimezone = $request->user()->customer->getTimezone();
        return view('account.activity', [
            'currentTimezone' => $currentTimezone
        ]);
    }

    public function saveAutoThemeMode(Request $request)
    {
        $request->session()->put('customer-auto-theme-mode', $request->theme_mode);
    }

    public function changeThemeMode(Request $request)
    {
        $customer = $request->user()->customer;

        // Save color scheme
        if ($request->isMethod('post')) {
            $customer->theme_mode = $request->theme_mode;
            $customer->save();
        }
    }
}
