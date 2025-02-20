<?php

namespace Acelle\Http\Controllers\Api;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;
use Acelle\Model\Customer;
use Acelle\Model\Language;
use Acelle\Library\Facades\SubscriptionFacade;
use Acelle\Model\SubscriptionLog;
use Acelle\Library\TransactionResult;

/**
 * /api/v1/customers - API controller for managing customers.
 */
class CustomerController extends Controller
{
    public function index()
    {
        $customers = \Acelle\Model\Customer::select('customers.*')->get();

        return \Response::json($customers, 200);
    }

    /**
     * Create new customer.
     *
     * POST /api/v1/customers/store
     *
     * @param \Illuminate\Http\Request $request All customer information.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Get current user
        $current_user = \Auth::guard('api')->user();

        //
        if (!Language::where('id', $request->language_id)->exists()) {
            return \Response::json(array('status' => 0, 'message' => 'Language with ID ['.$request->language_id.'] not found'), 404);
        }

        // validation
        list($validator, $customer, $user) = \Acelle\Model\Customer::createCustomerWithDefaultUser(
            $admin = $current_user->admin,
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
        );

        //  errors
        if (!$validator->errors()->isEmpty()) {
            return response()->json($validator->messages(), 403);
        }

        return \Response::json(array(
            'status' => 1,
            'message' => trans('messages.customer.created'),
            'customer_uid' => $customer->uid,
            'api_token' => $user->api_token
        ), 200);
    }

    /**
     * Update customer.
     *
     * PATCH /api/v1/customers
     *
     * @param \Illuminate\Http\Request $request All customer information.
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $uid)
    {
        // Get current user
        $current_user = \Auth::guard('api')->user();
        $customer = \Acelle\Model\Customer::findByUid($uid);

        // check if item exists
        if (!$customer) {
            return \Response::json(array('status' => 0, 'message' => 'Customer not found'), 404);
        }

        // authorize
        if (!$current_user->can('update', $customer)) {
            return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
        }

        // validation
        list($validator, $customer) = $customer->updateCustomer(
            $name = $request->name,
            $timezone = $request->timezone,
            $language_id = $request->language_id
        );

        //  errors
        if (!$validator->errors()->isEmpty()) {
            return response()->json($validator->messages(), 403);
        }

        return \Response::json(array(
            'status' => 1,
            'message' => trans('messages.customer.updated'),
            'customer_uid' => $customer->uid
        ), 200);
    }

    /**
     * Display the specified customer information.
     *
     * GET /api/v1/customers/{uid}
     *
     * @param int $id customer's uid
     *
     * @return \Illuminate\Http\Response
     */
    public function show($uid)
    {
        $user = \Auth::guard('api')->user();

        $customer = \Acelle\Model\Customer::findByUid($uid);

        // check if item exists
        if (!$customer) {
            return \Response::json(array('message' => 'Customer not found'), 404);
        }

        // authorize
        if (!$user->can('read', $customer)) {
            return \Response::json(array('message' => 'Unauthorized'), 401);
        }

        // Customer info
        $result = [
            'uid' => $customer->uid,
            'name' => $customer->name,
            'timezone' => $customer->timezone,
            'status' => $customer->status,
        ];

        // Customer contact
        $contact = $customer->contact;
        if ($contact) {
            $result['contact'] = [
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'company' => $contact->company,
                'address_1' => $contact->address_1,
                'address_2' => $contact->address_2,
                'country' => $contact->countryName(),
                'state' => $contact->state,
                'city' => $contact->city,
                'zip' => $contact->zip,
                'phone' => $contact->phone,
                'url' => $contact->url,
                'email' => $contact->email,
            ];
        }

        // Current subscription
        $subscription = $customer->getNewOrActiveGeneralSubscription();
        if ($subscription) {
            $result['current_subscription'] = [
                'uid' => $subscription->uid,
                'plan_name' => $subscription->plan ? $subscription->plan->name : null,
                'current_period_ends_at' => $subscription->current_period_ends_at,
                'cancelled_at' => $subscription->cancelled_at,
                'terminated_at' => $subscription->terminated_at,
                'status' => $subscription->status,
            ];
        }

        return \Response::json(['customer' => $result], 200);
    }

    /**
     * Enable customer.
     *
     * PATCH /api/v1/customers/{uid}
     *
     * @param int $id customer's uid
     *
     * @return \Illuminate\Http\Response
     */
    public function enable($uid)
    {
        $user = \Auth::guard('api')->user();

        $customer = \Acelle\Model\Customer::findByUid($uid);

        // check if item exists
        if (!$customer) {
            return \Response::json(array('status' => 0, 'message' => 'Customer not found'), 404);
        }

        // authorize
        if (!$user->can('enable', $customer)) {
            return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
        }

        $customer->enable();

        return \Response::json(array(
            'status' => 1,
            'message' => trans('messages.customer.enabled'),
            'customer_uid' => $customer->uid
        ), 200);
    }

    /**
     * Disable customer.
     *
     * PATCH /api/v1/customers/{uid}
     *
     * @param int $id customer's uid
     *
     * @return \Illuminate\Http\Response
     */
    public function disable($uid)
    {
        $user = \Auth::guard('api')->user();

        $customer = \Acelle\Model\Customer::findByUid($uid);

        // check if item exists
        if (!$customer) {
            return \Response::json(array('status' => 0, 'message' => 'Customer not found'), 404);
        }

        // authorize
        if (!$user->can('disable', $customer)) {
            return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
        }

        $customer->disable();

        return \Response::json(array(
            'status' => 1,
            'message' => trans('messages.customer.disabled'),
            'customer_uid' => $customer->uid
        ), 200);
    }

    /**
     * Assign plan to customer.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function assignPlan(Request $request, $uid, $plan_uid)
    {
        $user = \Auth::guard('api')->user();
        $customer = \Acelle\Model\Customer::findByUid($uid);
        $offlineGateway = \Acelle\Library\Facades\Billing::getGateway('offline');

        // check if item exists
        if (!$customer) {
            return \Response::json(array('status' => 0, 'message' => 'Customer not found'), 404);
        }

        // authorize
        if (!$user->can('assignPlan', $customer)) {
            return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
        }

        $plan = \Acelle\Model\PlanGeneral::findByUid($plan_uid);

        // check if item exists
        if (!$plan) {
            return \Response::json(array('status' => 0, 'message' => 'Can not find plan with id: ' . $plan_uid), 404);
        }

        // check if item active
        if (!$plan->isActive()) {
            return \Response::json(array('status' => 0, 'message' => 'Plan is not active'), 404);
        }

        // check offline gateway if disale billing
        if ($request->disable_billing && $request->disable_billing !== 'false') {
            // check if offline payment is not active
            if (!$offlineGateway->isActive() || !\Acelle\Library\Facades\Billing::isGatewayEnabled($offlineGateway)) {
                return \Response::json(array('status' => 0, 'message' => 'You need to enable Offline payment in order to disable billing!'), 404);
            }
        }

        // rollback if something went wrong
        \DB::transaction(function () use ($plan, $customer, $request, $offlineGateway) {
            // force terminate if customer already has new or active subscription
            if ($request->force == 'true') {
                // terminate if customer already has new or active subscription
                if ($customer->getNewOrActiveGeneralSubscription()) {
                    $customer->getNewOrActiveGeneralSubscription()->terminate();
                }
            }

            $subscription = $customer->assignGeneralPlan($plan);

            // * Disable billing information: customer does not need to pay the invoice. Pass by the billing and checkout step
            // * assignPlan always create a subscription with an unpaid invoice.
            // * So just checkout the invoice with result = success
            // * By design: when the subscription invoice checkout successfully, the callback function will be triggered
            //              to set subscription as active automatically.
            if ($request->disable_billing && $request->disable_billing !== 'false') {
                $subscription->getItsOnlyUnpaidInitInvoice()->checkout($offlineGateway, function () {
                    return new \Acelle\Library\TransactionResult(\Acelle\Library\TransactionResult::RESULT_DONE);
                });
            }
        });

        return \Response::json(array(
            'status' => 1,
            'message' => 'Assigned '.$customer->displayName().' plan to '.$plan->name.' successfully.',
            'customer_uid' => $customer->uid,
            'plan_uid' => $plan->uid
        ), 200);
    }

    /**
     * Assign plan to customer.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function subscriptionUpdate(Request $request, $uid)
    {
        $customer = \Acelle\Model\Customer::findByUid($uid);

        // terminate if customer already has new or active subscription
        if ($customer->getNewOrActiveGeneralSubscription()) {
            $customer->getNewOrActiveGeneralSubscription()->terminate();
        }

        return $this->assignPlan($request, $uid, $request->plan_uid);
    }

    /**
     * Generate one time token.
     *
     * PATCH /api/v1/customers/{uid}/login-token
     *
     * @param int $id customer's uid
     *
     * @return \Illuminate\Http\Response
     */
    public function loginToken(Request $request)
    {
        $user = \Auth::guard('api')->user();

        // if for another customer
        if ($request->customer_uid) {
            $customer = Customer::findByUid($request->customer_uid);

            // authorize
            if (!$user->can('loginAs', $customer)) {
                return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
            }

            $user = $customer->getFirstUserLegacy();
        }

        $user->generateOneTimeToken();

        echo json_encode(array(
            'token' => $user->one_time_api_token,
            'url' => action('Controller@tokenLogin', ['token' => $user->one_time_api_token]),
        ), JSON_UNESCAPED_SLASHES);
    }

    public function destroy($uid)
    {
        $user = \Auth::guard('api')->user();

        $customer = \Acelle\Model\Customer::findByUid($uid);

        // check if item exists
        if (!$customer) {
            return \Response::json(array('status' => 0, 'message' => 'Customer not found'), 404);
        }

        // authorize
        if (!$user->can('delete', $customer)) {
            return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
        }

        $uid = $customer->uid;
        $customer->deleteAccount();

        return \Response::json(array(
            'status' => 1,
            'message' => trans('messages.customers.deleted'),
            'customer_uid' => $uid
        ), 200);
    }

    public function changePlan(Request $request, $uid, $plan_uid)
    {
        $user = \Auth::guard('api')->user();
        $customer = \Acelle\Model\Customer::findByUid($uid);

        // check if item exists
        if (!$customer) {
            return \Response::json(array('status' => 0, 'message' => 'Customer not found'), 404);
        }

        // authorize
        if (!$user->can('changePlan', $customer)) {
            return \Response::json(array('status' => 0, 'message' => 'Unauthorized'), 401);
        }

        $plan = \Acelle\Model\PlanGeneral::findByUid($plan_uid);

        // check if item exists
        if (!$plan) {
            return \Response::json(array('status' => 0, 'message' => 'Can not find plan with id: ' . $plan_uid), 404);
        }

        // check if item active
        if (!$plan->isActive()) {
            return \Response::json(array('status' => 0, 'message' => 'Plan is not active'), 404);
        }

        // get current active subscription
        $subscription = $customer->getCurrentActiveGeneralSubscription();

        if (!$subscription) {
            return \Response::json(array('status' => 0, 'message' => 'The customer does not have a current active subscription.'), 404);
        }

        // Đã có change plan invoice thì xóa nó luôn, change plan khác
        if ($subscription->getItsOnlyUnpaidChangePlanInvoice()) {
            $subscription->getItsOnlyUnpaidChangePlanInvoice()->delete();
        }

        try {
            $changePlanInvoice = null;
            $message = null;

            \DB::transaction(function () use ($customer, $subscription, $plan, &$changePlanInvoice, &$message) {
                // tạo change plan invoice
                $changePlanInvoice = $subscription->createChangePlanInvoice($plan);
                $changePlanInvoice = $changePlanInvoice->mapType();

                // Log
                SubscriptionFacade::log($subscription, SubscriptionLog::TYPE_CHANGE_PLAN_INVOICE, $changePlanInvoice->uid, [
                    'plan' => $subscription->getPlanName(),
                    'new_plan' => $plan->name,
                    'amount' => $changePlanInvoice->total(),
                ]);

                // Trường hợp invoice total = 0 thì pay nothing và set done luôn cho change plan invoice
                if ($changePlanInvoice->total() == 0) {
                    $changePlanInvoice->checkout($customer->getPreferredPaymentGateway(), function ($invoice) {
                        return new TransactionResult(TransactionResult::RESULT_DONE);
                    });

                    $message = 'Plan changed';
                } else {
                    $message = "Change plan invoice created. User might need to proceed with logging to the client area, then go to the Profile > Subscription dashboard to pay the invoice";
                }
            });

            // return to subscription
            return \Response::json(array(
                'status' => 1,
                'message' => $message,
                'customer_uid' => $customer->uid,
                'plan_uid' => $plan->uid
            ), 200);
        } catch (\Throwable $e) {
            return \Response::json(array('status' => 0, 'message' => $e->getMessage()), 500);
        }
    }

    public function findByEmail($email)
    {
        $user = \Auth::guard('api')->user();

        $u = \Acelle\Model\User::whereEmail($email)->first();

        // check if item exists
        if (!$u) {
            return \Response::json(array('message' => 'Customer not found by email: '. $email), 404);
        }

        $customer = $u->customer;

        // authorize
        if (!$user->can('read', $customer)) {
            return \Response::json(array('message' => 'Unauthorized'), 401);
        }

        // Customer info
        $result = [
            'uid' => $customer->uid,
            'name' => $customer->name,
            'timezone' => $customer->timezone,
            'status' => $customer->status,
        ];

        // Customer contact
        $contact = $customer->contact;
        if ($contact) {
            $result['contact'] = [
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'company' => $contact->company,
                'address_1' => $contact->address_1,
                'address_2' => $contact->address_2,
                'country' => $contact->countryName(),
                'state' => $contact->state,
                'city' => $contact->city,
                'zip' => $contact->zip,
                'phone' => $contact->phone,
                'url' => $contact->url,
                'email' => $contact->email,
            ];
        }

        // Current subscription
        $subscription = $customer->getNewOrActiveGeneralSubscription();
        if ($subscription) {
            $result['current_subscription'] = [
                'uid' => $subscription->uid,
                'plan_name' => $subscription->plan ? $subscription->plan->name : null,
                'current_period_ends_at' => $subscription->current_period_ends_at,
                'cancelled_at' => $subscription->cancelled_at,
                'terminated_at' => $subscription->terminated_at,
                'status' => $subscription->status,
            ];
        }

        return \Response::json(['customer' => $result], 200);
    }
}
