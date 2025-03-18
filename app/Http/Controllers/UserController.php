<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\Customer;
use Acelle\Model\User;
use Acelle\Library\Facades\Hook;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    /**
     * Log in back user.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function loginBack(Request $request)
    {
        $customer = $request->user()->customer;
        $id = \Session::pull('orig_user_uid');
        $orig_user = User::findByUid($id);

        \Auth::login($orig_user);

        return redirect()->action('Admin\UserController@index', [
            'customer_uid' => $customer->uid,
        ]);
    }

    /**
     * Activate user account.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function activate(Request $request, $token)
    {
        $userActivation = \Acelle\Model\UserActivation::where('token', '=', $token)->first();

        if (!$userActivation) {
            return view('notAuthorized');
        } else {
            $userActivation->user->setActivated();

            // Execute registered hooks
            Hook::execute('customer_added', [$userActivation->user->customer]);

            $request->session()->put('user-activated', trans('messages.user.activated'));

            // assignPlan
            if ($request->plan_uid) {
                $userActivation->user->customer->assignGeneralPlan(\Acelle\Model\PlanGeneral::findByUid($request->plan_uid));
            }

            if (isset($request->redirect)) {
                return redirect()->away(urldecode($request->redirect));
            } else {
                return redirect()->action('HomeController@index');
            }
        }
    }

    /**
     * Resen activation confirmation email.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function resendActivationEmail(Request $request)
    {
        $user = User::findByUid($request->uid);

        try {
            $user->sendActivationMail($user->email, action('HomeController@index'));
        } catch (\Exception $e) {
            return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_email_service').': '.$e->getMessage() ]);
        }

        return view('users.registration_confirmation_sent');
    }

    /**
     * User registration.
     */
    public function register(Request $request)
    {
        if (\Acelle\Model\Setting::get('enable_user_registration') == 'no') {
            return $this->notAuthorized();
        }

        // If already logged in
        if (!is_null($request->user())) {
            return redirect()->action('SubscriptionController@index');
        }

        // Initiate customer object for filling the form
        $customer = Customer::newCustomer();
        $user = $customer->newUser();

        // save posted data
        if ($request->isMethod('post')) {
            // Just for refilling the form (UI)
            $user->fill($request->all());
            $customer->fill($request->all());

            // Captcha check
            if (\Acelle\Model\Setting::get('registration_recaptcha') == 'yes') {
                // @hCaptcha
                if (\Acelle\Model\Setting::getCaptchaProvider() == 'hcaptcha') {
                    $hcaptcha = \Acelle\Hcaptcha\Client::initialize();
                    $success = $hcaptcha->check($request);
                    if (!$success) {
                        // validation
                        $validator = \Validator::make([], [
                            'captcha_invalid' => 'required',
                        ]);

                        return view('users.register', [
                            'customer' => $customer,
                            'user' => $user,
                            'errors' => $validator->errors(),
                        ]);
                    }
                    // @reCaptcha: default
                } else {
                    $success = \Acelle\Library\Tool::checkReCaptcha($request);
                    if (!$success) {
                        // validation
                        $validator = \Validator::make([], [
                            'recaptcha_invalid' => 'required',
                        ]);

                        return view('users.register', [
                            'customer' => $customer,
                            'user' => $user,
                            'errors' => $validator->errors(),
                        ]);
                    }
                }
            }

            // validation
            list($validator, $customer, $user) = \Acelle\Model\Customer::createCustomerWithDefaultUser(
                $admin = null,
                $name = $request->name,
                $timezone = $request->timezone,
                $language_id = $request->language_id,
                $email = $request->email,
                $password = $request->password,
                $passwordConfirmation = $request->password,
                $first_name = $request->first_name,
                $last_name = $request->last_name,
                $image = $request->image,
                $role_uid = \Acelle\Model\Role::getDefaultAdminRole()->uid,
                $image = $request->phone
            );

            //  errors
            if (!$validator->errors()->isEmpty()) {
                return view('users.register', [
                    'customer' => $customer,
                    'user' => $user,
                    'errors' => $validator->errors(),
                ]);
            }


            // Customer Contact
            $customerContact = \Acelle\Model\Contact::create(['first_name' => $request->first_name,'last_name' => $request->last_name,'company' => $request->name, 'email' => $request->email]);
            $customer->contact_id = $customerContact->id;
            $customer->save();

            // Generate info
            $list = $customer->newMailList();

            // Save contact
            $contact = \Acelle\Model\Contact::create(['first_name' => $request->first_name,'last_name' => $request->last_name,'company' => $request->name, 'email' => $request->email]);
            $list->fill(['name' => 'Newsletter','from_name' => $request->name,'from_email' => $request->email]);
            $list->customer_id = $customer->id;
            $list->contact_id = $contact->id;
            $list->save();

            // user email verification
            if (true) {
                // Send registration confirmation email
                try {
                    $user->sendActivationMail($customer->displayName(), $request->plan_uid);
                } catch (\Exception $e) {
                    return view('somethingWentWrong', ['message' => trans('messages.something_went_wrong_with_email_service') . ": " . $e->getMessage()]);
                }

                return view('users.register_confirmation_notice');

                // no email verification
            } else {
                $user->setActivated();
                return redirect()->route('login');
            }
        }

        return view('users.register', [
            'customer' => $customer,
            'user' => $user,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('users.index');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        $users = $request->user()->customer->users()
            ->search($request->keyword)
            ->orderBy($request->sort_order ?? 'created_at', $request->sort_direction ?? 'desc')
            ->paginate($request->per_page);

        return view('users._list', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $customer = $request->user()->customer;
        $user = $customer->newUser();

        // check quota limit
        if ($customer->getMaxUserQuota() <= $customer->users()->count()) {
            return $this->noMoreItem();
        }

        return view('users.create', [
            'user' => $user,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Get current user
        $user = $request->user()->customer->newUser();

        //
        [$result, $errors] = $user->saveUser(
            $email = $request->email,
            $password = $request->password,
            $passwordConfirmation = $request->password_confirmation,
            $first_name = $request->first_name,
            $last_name = $request->last_name,
            $image = $request->image,
            $role_uid = $request->role_uid,
            $phone = $request->phone
        );


        // redirect if fails
        if (!$result) {
            return response()->view('users.create', [
                'user' => $user,
                'errors' => $errors,
            ], 400);
        }

        return redirect()->action('UserController@index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $user = \Acelle\Model\User::findByUid($id);

        return view('users.edit', [
            'user' => $user,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // Get current user
        $user = \Acelle\Model\User::findByUid($id);

        // Make sure account has at least one role with code organization_admin
        if ($request->role_uid) {
            // find role
            $role = \Acelle\Model\Role::findByUid($request->role_uid);

            // if updating role is organization_admin ==> ok
            if ($role->code == 'organization_admin') {

            } else { // if updating role is not organization_admin
                // ==> kiểm tra tiếp xem là còn user nào có role này không ngoài user đó cùng account
                $exists = $user->customer->users()
                    ->where('id', '!=', $user->id)
                    ->whereHas('roles', function ($q) {
                        $q->whereCode('organization_admin');
                    })
                    ->exists();

                // check
                if (!$exists) {
                    return response()->view('users.edit', [
                        'user' => $user,
                        'errors' => new \Illuminate\Support\MessageBag([
                            'error' => trans('messages.account.at_least_one_admin_user')
                        ]),
                    ], 400);
                }
            }
        }

        //
        [$result, $errors] = $user->saveUser(
            $email = $request->email,
            $password = $request->password,
            $passwordConfirmation = $request->password_confirmation,
            $first_name = $request->first_name,
            $last_name = $request->last_name,
            $image = $request->image,
            $role_uid = $request->role_uid,
            $phone = $request->phone
        );

        // redirect if fails
        if (!$result) {
            return response()->view('users.edit', [
                'user' => $user,
                'errors' => $errors,
            ], 400);
        }

        // Remove image
        if ($request->_remove_image == 'true') {
            $user->removeProfileImage();
        }

        return redirect()->action('UserController@index');
    }

    /**
     * Enable item.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function enable(Request $request)
    {
        $users = \Acelle\Model\User::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($users->get() as $user) {
            // authorize
            if ($user->customer->can('enable', $user)) {
                $user->enable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.user.disabled');
    }

    /**
     * Disable item.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function disable(Request $request)
    {
        $users = \Acelle\Model\User::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($users->get() as $user) {
            // authorize
            if ($user->customer->can('disable', $user)) {
                $user->disable();
            }
        }

        // Redirect to my lists page
        echo trans('messages.user.disabled');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        if (isSiteDemo()) {
            return response()->json(["message" => trans('messages.operation_not_allowed_in_demo')], 404);
        }

        $users = \Acelle\Model\User::whereIn(
            'uid',
            is_array($request->uids) ? $request->uids : explode(',', $request->uids)
        );

        foreach ($users->get() as $user) {
            // authorize
            if (!$user->customer->can('delete', $user)) {
                return;
            }
        }

        foreach ($users->get() as $user) {
            $user->delete();
        }

        // Redirect to my lists page
        echo trans('messages.user.deleted');
    }

    public function verifySelectMethod(Request $request)
    {
        if (!$request->user()->is2FAEnabled()) {
            $url = session()->get('2fa.redirect', action('HomeController@index'));
            return redirect()->away($url);
        }

        // send verifucation email
        if ($request->isMethod('post')) {
            $request->user()->sendVerifyCodeEMail();

            // redirect
            return redirect(action('Email2FAController@emailVerify'));
        }

        return view('auth.2fa.select');
    }
}
