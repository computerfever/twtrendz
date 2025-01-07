<?php

namespace Acelle\Http\Controllers\Api\Public;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;

/**
 * /api/v1/payment - API controller for managing subscriptions.
 */
class EmailVerificationController extends Controller
{
    public function getFeaturePlan()
    {
        $plan = \Acelle\Model\Plan::first();

        return response()->json([
            'plan_uid' => $plan->uid,
            'name'  => $plan->name,
            'description' => $plan->description,
            'display_price' => format_price($plan->price, $plan->currency->format, true),
        ]);
    }

    public function findOrCreateCustomer(Request $request)
    {
        // find or create a customer
        list($customer, $user) = \Acelle\Model\Customer::findOrCreateCustomerByEmail($request->email);

        return response()->json([
            'customer_uid' => $customer->uid,
            'api_token'  => $user->api_token,
        ]);
    }

    /**
     * Get all payment.
     *
     * GET /api/v1/email-verification/checkout
     *
     * @return \Illuminate\Http\Response
     */
    public function subscribe(Request $request)
    {
        // random email
        $customer = \Acelle\Model\Customer::findByUid($request->customer_uid);
        $plan = \Acelle\Model\PlanGeneral::findByUid($request->plan_uid);

        // Find current subscription if exists
        $subscription = $customer->getNewOrActiveGeneralSubscription();

        // Customer does not have any subscription
        if (!$subscription) {
            // Assign Free Plan
            try {
                $subscription = $customer->assignGeneralPlan($plan);
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ], 500);
            }
        }

        // Return
        return response()->json([
            'subscription_uid' => $subscription->uid,
        ]);
    }

    public function getSubscription(Request $request)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($request->subscription_uid);

        return response()->json([
            'status' => $subscription->status,
            'subscription_uid' => $subscription->uid,
            'customer_uid' => $subscription->customer->uid,
            'api_token' => $subscription->customer->getFirstUserLegacy()->api_token,
            'credits' => $subscription->getVerifyEmailCreditTracker()->getRemainingCredits(),
        ]);
    }

    public function getCheckoutUrl(Request $request)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($request->subscription_uid);

        // init invoice
        $initInvoice = $subscription->getItsOnlyUnpaidInitInvoice();

        // payment method
        $subscription->customer->updatePaymentMethod([
            'method' => $request->payment_method,
        ]);

        // checkout url
        $checkoutUrl = $subscription->customer->getPreferredPaymentGateway()->getCheckoutUrl($initInvoice);

        // Return checkout url
        return response()->json([
            'checkout_url' => action('\Acelle\Http\Controllers\EmailVerificationController@redirect', [
                'api_token' => $subscription->customer->getFirstUserLegacy()->api_token,
                'subscription_uid' => $subscription->uid,
                'checkout_url' => $checkoutUrl,
                'return_url' => $request->return_url,
            ]),
        ]);
    }

}
