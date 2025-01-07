<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Model\Subscription;
use Acelle\Cashier\Cashier;

class CustomerController extends Controller
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
        if ($request->user()->admin) {
            return redirect()->action('Admin\HomeController@index');
        }

        $customer = $request->user()->customer;
        $id = \Session::pull('orig_user_uid');
        $orig_user = \Acelle\Model\User::findByUid($id);

        \Auth::login($orig_user);

        return redirect()->action('Admin\UserController@index', [
            'customer_uid' => $customer->uid,
        ]);
    }

    /**
     * Admin area.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function adminArea(Request $request)
    {
        $id = \Session::get('orig_user_uid');
        $orig_user = \Acelle\Model\User::findByUid($id);

        // Get current subscription
        $customer = $request->user()->customer;
        $subscription = $customer->getNewOrActiveGeneralSubscription();

        $next_billing_date = null;
        if ($subscription) {
            $next_billing_date = $subscription->current_period_ends_at;
        }

        return view('customers.admin_area', [
            'customer' => $customer,
            'subscription' => $subscription,
            'next_billing_date' => $next_billing_date,
        ]);
    }
}
