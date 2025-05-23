<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\PlanGeneral;
use Acelle\Model\SubscriptionLog;
use Acelle\Library\Facades\Billing;
use Acelle\Library\Facades\SubscriptionFacade;
use Acelle\Model\InvoiceNewSubscription;
use Acelle\Model\InvoiceRenewSubscription;
use Acelle\Model\InvoiceChangePlan;
use Acelle\Model\Transaction;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        // Trick here: these tasks are supposed to be executed in the background only
        // SubscriptionFacade::endExpiredSubscriptions();
        // SubscriptionFacade::createRenewInvoices();
        // SubscriptionFacade::autoChargeRenewInvoices();

        // init
        $customer = $request->user()->customer;
        $subscription = $customer->getNewOrActiveGeneralSubscription();

        // 1. HAVE NOT HAD NEW/ACTIVE SUBSCRIPTION YET
        if (!$subscription) {
            // User chưa có subscription sẽ được chuyển qua chọn plan
            return redirect()->action('SubscriptionController@selectPlan');
        }

        // 2. IF PLAN NOT ACTIVE
        if (!$subscription->planGeneral->isActive()) {
            return response()->view('errors.general', [ 'message' => __('messages.subscription.error.plan-not-active', [ 'name' => $subscription->planGeneral->name]) ]);
        }

        // 3. SUBSCRIPTION IS NEW
        if ($subscription->isNew()) {
            $invoice = $subscription->getItsOnlyUnpaidInitInvoice();

            return redirect()->action('SubscriptionController@payment', [
                'invoice_uid' => $invoice->uid,
            ]);
        }

        // 3. SUBSCRIPTION IS ACTIVE, SHOW DETAILS PAGE
        return view('subscription.index', [
            'subscription' => $subscription,
            'plan' => $subscription->planGeneral,
        ]);
    }

    public function selectPlan(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        // init
        $customer = $request->user()->customer;
        $subscription = $customer->getNewOrActiveGeneralSubscription();

        return view('subscription.selectPlan', [
            'plans' => PlanGeneral::getAvailableGeneralPlans(),
            'subscription' => $subscription,
            'getLastCancelledOrEndedGeneralSubscription' => $customer->getLastCancelledOrEndedGeneralSubscription(),
        ]);
    }

    public function assignPlan(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        $customer = $request->user()->customer;
        $plan = PlanGeneral::findByUid($request->plan_uid);

        if(empty($customer->contact->first_name) or empty($customer->contact->last_name) or empty($customer->contact->company) or empty($customer->contact->email) or empty($customer->contact->address_1) or empty($customer->contact->country_id) or empty($customer->contact->url)){
            return redirect()->action('AccountController@contact');
        }

        // already has subscription
        if ($customer->getCurrentActiveGeneralSubscription()) {
            throw new \Exception('Customer already has active subscription!');
        }

        // subscription hiện đang new. Customer muốn thay đổi plan khác?
        // delete luôn subscription
        $current = $customer->subscriptions()->general()->newOrActive()->first();
        if ($current) {
            $current->deleteAndCleanup();
        }

        // assign plan
        $subscription = $customer->assignGeneralPlan($plan);

        //
        $invoice = $subscription->getItsOnlyUnpaidInitInvoice();

        // Nếu invoice cho plan mà bỏ qua payment when free thì return view confirmation
        if (
            in_array($invoice->type, [
                InvoiceNewSubscription::TYPE_NEW_SUBSCRIPTION,
                InvoiceRenewSubscription::TYPE_RENEW_SUBSCRIPTION,
                InvoiceChangePlan::TYPE_CHANGE_PLAN,
            ]) &&
            $invoice->no_payment_required_when_free &&
            $invoice->isFree()
        ) {
            // return to subscription
            return redirect()->action('SubscriptionController@noPaymentConfirmation', [
                'invoice_uid' => $invoice->uid,
            ]);
        }

        // redirect to billing information step
        return redirect()->action('SubscriptionController@billingInformation', [
            'invoice_uid' => $invoice->uid,
        ]);
    }

    public function billingInformation(Request $request)
    {
        $customer = $request->user()->customer;
        $invoice = $customer->invoices()->where('uid', '=', $request->invoice_uid)->first();
        $billingAddress = $customer->getDefaultBillingAddress();

        if(empty($customer->contact->first_name) or empty($customer->contact->last_name) or empty($customer->contact->company) or empty($customer->contact->email) or empty($customer->contact->address_1) or empty($customer->contact->country_id) or empty($customer->contact->url)){
            return redirect()->action('AccountController@contact');
        }

        // can not found the invoice
        if (!$invoice) {
            return redirect()->action('SubscriptionController@index')
                ->with('alert-warning', "The invoice with ID [{$request->invoice_uid}] does not exist!");
        }

        // can not found the invoice
        if ($invoice->isPaid()) {
            return redirect()->action('InvoiceController@show', $request->invoice_uid);
        }

        // always update invoice price from plan
        $invoice = $invoice->mapType();

        // Nếu invoice cho plan mà bỏ qua payment when free thì return view confirmation
        if (
            in_array($invoice->type, [
                InvoiceNewSubscription::TYPE_NEW_SUBSCRIPTION,
                InvoiceRenewSubscription::TYPE_RENEW_SUBSCRIPTION,
                InvoiceChangePlan::TYPE_CHANGE_PLAN,
            ]) &&
            $invoice->no_payment_required_when_free &&
            $invoice->isFree()
        ) {
            // return to subscription
            return redirect()->action('SubscriptionController@noPaymentConfirmation', [
                'invoice_uid' => $invoice->uid,
            ]);
        }

        // Save posted data
        if ($request->isMethod('post')) {
            $validator = $invoice->updateBillingInformation($request->all());

            // redirect if fails
            if ($validator->fails()) {
                return response()->view('subscription.billingInformation', [
                    'invoice' => $invoice,
                    'billingAddress' => $billingAddress,
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Khúc này customer cập nhật thông tin billing information cho lần tiếp theo
            $customer->updateBillingInformationFromInvoice($invoice);

            $request->session()->flash('alert-success', trans('messages.billing_address.updated'));

            // return to subscription
            return redirect()->action('SubscriptionController@payment', [
                'invoice_uid' => $invoice->uid,
            ]);
        }

        return view('subscription.billingInformation', [
            'invoice' => $invoice,
            'billingAddress' => $billingAddress,
        ]);
    }

    public function payment(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        // Get current customer
        $customer = $request->user()->customer;

        // get invoice
        $invoice = $customer->invoices()->where('uid', '=', $request->invoice_uid)->first();

        // can not found the invoice
        if (!$invoice) {
            // throw new \Exception("The invoice with ID [{$request->invoice_uid}] does not exist!");
            return redirect()->action('SubscriptionController@index')
                ->with('alert-warning', "The invoice with ID [{$request->invoice_uid}] does not exist!");
        }

        // can not found the invoice
        if ($invoice->isPaid()) {
            return redirect()->action('InvoiceController@show', $request->invoice_uid);
        }

        // no unpaid invoice found
        if (!$invoice) {
            // throw new \Exception('Can not find unpaid invoice with id:' . $request->invoice_uid);
            // just redirect to index
            return redirect()->action('SubscriptionController@index');
        }

        // always update invoice price from plan
        $invoice = $invoice->mapType();

        // nếu đang có pending transaction thì luôn show màn hình pending
        if ($invoice->getPendingTransaction()) {
            return view('subscription.pending', [
                'invoice' => $invoice,
                'transaction' => $invoice->getPendingTransaction(),
            ]);
        }

        // Nếu invoice cho plan mà bỏ qua payment when free thì return view confirmation
        if (
            in_array($invoice->type, [
                InvoiceNewSubscription::TYPE_NEW_SUBSCRIPTION,
                InvoiceRenewSubscription::TYPE_RENEW_SUBSCRIPTION,
                InvoiceChangePlan::TYPE_CHANGE_PLAN,
            ]) &&
            $invoice->no_payment_required_when_free &&
            $invoice->isFree()
        ) {
            // return to subscription
            return redirect()->action('SubscriptionController@noPaymentConfirmation', [
                'invoice_uid' => $invoice->uid,
            ]);
        }

        // luôn luôn require billing information
        if (!$invoice->hasBillingInformation()) {
            return redirect()->action('SubscriptionController@billingInformation', [
                'invoice_uid' => $invoice->uid,
            ]);
        }

        return view('subscription.payment', [
            'invoice' => $invoice,
        ]);
    }

    public function checkout(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        $customer = $request->user()->customer;
        $invoice = $customer->invoices()->where('uid', '=', $request->invoice_uid)->first()->mapType();

        // can not found the invoice
        if (!$invoice) {
            return redirect()->action('SubscriptionController@index')
                ->with('alert-warning', "The invoice with ID [{$request->invoice_uid}] does not exist!");
        }

        // can not found the invoice
        if ($invoice->isPaid()) {
            return redirect()->action('InvoiceController@show', $request->invoice_uid);
        }

        // always update invoice price from plan
        $invoice->mapType()->refreshPrice();

        // Luôn đặt payment method mặc định cho customer là lần chọn payment gần nhất
        $request->user()->customer->updatePaymentMethod([
            'method' => $request->payment_method,
        ]);

        // Bỏ qua việc nhập card information khi subscribe plan with trial
        if (
            \Acelle\Model\Setting::get('not_require_card_for_trial') == 'yes' &&
            $invoice->type == InvoiceNewSubscription::TYPE_NEW_SUBSCRIPTION && // chỉ có newSub invoice mới bỏ qua trial
            in_array($customer->getPreferredPaymentGateway()->getType(), ['stripe', 'braintree', 'paystack']) && // @todo moving this to interface
            $invoice->getPlan()->hasTrial() &&
			$customer->trial_over != 1
        ) {
            $invoice->checkout($customer->getPreferredPaymentGateway(), function () {
                return new \Acelle\Library\TransactionResult(\Acelle\Library\TransactionResult::RESULT_DONE);
            });

            return redirect()->action('SubscriptionController@index');
        }

        // set return url
        Billing::setReturnUrl(action('SubscriptionController@index'));

        // redirect to service checkout
        return redirect()->away($customer->getPreferredPaymentGateway()->getCheckoutUrl($invoice));
    }

    public function cancelInvoice(Request $request, $uid)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        $invoice = \Acelle\Model\Invoice::findByUid($uid);

        // return to select plan if sub is NEW
        if ($request->user()->customer->getNewGeneralSubscription()) {
            return redirect()->action('SubscriptionController@selectPlan');
        }

        if (!$request->user()->customer->can('delete', $invoice)) {
            return $this->notAuthorized();
        }

        $invoice->cancel();

        // Redirect to my subscription page
        $request->session()->flash('alert-success', trans('messages.invoice.cancelled'));
        return redirect()->action('SubscriptionController@index');
    }

    /**
     * Change plan.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     **/
    public function changePlan(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        $customer = $request->user()->customer;
        $subscription = $customer->getCurrentActiveGeneralSubscription();
        $gateway = $customer->getPreferredPaymentGateway();
        $plans = PlanGeneral::getAvailableGeneralPlans();

        // Authorization
        if (!$request->user()->customer->can('changePlan', $subscription)) {
            return $this->notAuthorized();
        }

        //
        if ($request->isMethod('post')) {
            $newPlan = PlanGeneral::findByUid($request->plan_uid);

            try {
                $changePlanInvoice = null;

                \DB::transaction(function () use ($subscription, $newPlan, &$changePlanInvoice) {
                    // set invoice as pending
                    $changePlanInvoice = $subscription->createChangePlanInvoice($newPlan);

                    // Log
                    SubscriptionFacade::log($subscription, SubscriptionLog::TYPE_CHANGE_PLAN_INVOICE, $changePlanInvoice->uid, [
                        'plan' => $subscription->getPlanName(),
                        'new_plan' => $newPlan->name,
                        'amount' => $changePlanInvoice->total(),
                    ]);
                });

                // return to subscription
                return redirect()->action('SubscriptionController@payment', [
                    'invoice_uid' => $changePlanInvoice->uid,
                ]);
            } catch (\Exception $e) {
                $request->session()->flash('alert-error', $e->getMessage());
                return redirect()->action('SubscriptionController@index');
            }
        }

        return view('subscription.change_plan', [
            'subscription' => $subscription,
            'gateway' => $gateway,
            'plans' => $plans,
        ]);
    }

    /**
     * Cancel subscription at the end of current period.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function disableRecurring(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        if (isSiteDemo()) {
            return response()->json(["message" => trans('messages.operation_not_allowed_in_demo')], 404);
        }

        $customer = $request->user()->customer;
        $subscription = $customer->getNewOrActiveGeneralSubscription();

        if ($request->user()->customer->can('disableRecurring', $subscription)) {
            $subscription->disableRecurring();
        }

        // Redirect to my subscription page
        $request->session()->flash('alert-success', trans('messages.subscription.disabled_recurring'));
        return redirect()->action('SubscriptionController@index');
    }


    /**
     * Cancel subscription at the end of current period.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function enableRecurring(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        $customer = $request->user()->customer;
        $subscription = $customer->getNewOrActiveGeneralSubscription();

        if ($request->user()->customer->can('enableRecurring', $subscription)) {
            $subscription->enableRecurring();
        }

        // Redirect to my subscription page
        $request->session()->flash('alert-success', trans('messages.subscription.enabled_recurring'));
        return redirect()->action('SubscriptionController@index');
    }

    /**
     * Cancel now subscription at the end of current period.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function cancelNow(Request $request)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        if (isSiteDemo()) {
            return response()->json(["message" => trans('messages.operation_not_allowed_in_demo')], 404);
        }

        $customer = $request->user()->customer;
        $subscription = $customer->getNewOrActiveGeneralSubscription();

        if ($request->user()->customer->can('cancelNow', $subscription)) {
            $subscription->cancelNow();
        }

        // Redirect to my subscription page
        $request->session()->flash('alert-success', trans('messages.subscription.cancelled_now'));
        return redirect()->action('SubscriptionController@index');
    }

    public function orderBox(Request $request)
    {
        $customer = $request->user()->customer;

        // get unpaid invoice
        $invoice = $customer->invoices()->unpaid()->where('uid', '=', $request->invoice_uid)->first()->mapType();

        // gateway fee
        if ($request->payment_method) {
            $gateway = Billing::getGateway($request->payment_method);

            // update invoice fee if trial and gatewaye need minimal fee for auto billing
            $invoice->updatePaymentServiceFee($gateway);
        }

        return view('subscription.orderBox', [
            'subscription' => $invoice->subscription,
            'bill' => $invoice->mapType()->getBillingInfo(),
            'invoice' => $invoice,
        ]);
    }

    public function verifyPendingTransaction(Request $request, $invoice_uid)
    {
        // authorize
        if (!$request->user()->customer->can('updateProfile', $request->user()->customer)) {
            return $this->notAuthorized();
        }

        $invoice = \Acelle\Model\Invoice::findByUid($invoice_uid);
        $transaction = $invoice->getPendingTransaction();

        // invoice đã paid thì trả về subscription page.
        if ($invoice->isPaid()) {
            return redirect()->action('SubscriptionController@index');
        }

        // không có pending transaction
        if (!$transaction) {
            throw new \Exception('Invoice này không có pending transaction! kiểm tra lại UI xem có trường hợp nào không có pending transaction mà qua đây không?');
        }

        // get gateway
        $gateway = Billing::getGateway($transaction->method);

        // gateway verify transaction from invoice
        try {
            // get transaction result from service
            $result = $gateway->verify($transaction);

            // handle transaction result
            $invoice->handleTransactionResult($result);
        } catch (\Throwable $e) {
            $request->session()->flash('alert-error', $e->getMessage());
            return redirect()->action('SubscriptionController@payment', [
                'invoice_uid' => $invoice->uid,
            ]);
        }

        //
        if ($result->isDone()) {
            return redirect()->action('SubscriptionController@index');
        } else {
            $request->session()->flash('alert-info', trans('messages.subscription.payment_status.refreshed'));

            return redirect()->action('SubscriptionController@payment', [
                'invoice_uid' => $invoice->uid,
            ]);
        }
    }

    public function noPaymentConfirmation(Request $request)
    {
        $invoice = $request->user()->customer->invoices()->where('uid', '=', $request->invoice_uid)->first();
        $invoice = $invoice->mapType();

        // can not found the invoice
        if (!$invoice) {
            return redirect()->action('SubscriptionController@index')
                ->with('alert-warning', "The invoice with ID [{$request->invoice_uid}] does not exist!");
        }

        //
        if (!in_array($invoice->type, [
            InvoiceNewSubscription::TYPE_NEW_SUBSCRIPTION,
            InvoiceRenewSubscription::TYPE_RENEW_SUBSCRIPTION,
            InvoiceChangePlan::TYPE_CHANGE_PLAN,
        ])) {
            return redirect()->action('SubscriptionController@index')
                ->with('alert-warning', "Invoice type is invalid!");
        }

        // can not found the invoice
        if ($invoice->isPaid()) {
            return redirect()->action('InvoiceController@show', $request->invoice_uid);
        }

        // check settings
        if (!$invoice->no_payment_required_when_free) {
            return redirect()->action('SubscriptionController@index')
                ->with('alert-warning', "The plan no_payment_required_when_free setting is false!");
        }

        // check settings
        if (!$invoice->isFree()) {
            return redirect()->action('SubscriptionController@index')
                ->with('alert-warning', "The invoice total is not free!");
        }

        // Save posted data
        if ($request->isMethod('post')) {
            // checkout no payment
            $invoice->bypassPayment();

            return redirect()->action('SubscriptionController@index');
        }

        return view('subscription.noPaymentConfirmation', [
            'invoice' => $invoice,
        ]);
    }
}
