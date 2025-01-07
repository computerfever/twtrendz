<?php

namespace Acelle\Http\Controllers;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;

/**
 * /api/v1/payment - API controller for managing subscriptions.
 */
class EmailVerificationController extends Controller
{
    public function redirect(Request $request, $api_token)
    {
        $subscription = \Acelle\Model\Subscription::findByUid($request->subscription_uid);

        // login
        \Auth::login($subscription->customer->getFirstUserLegacy());

        // 2FA
        $subscription->customer->getFirstUserLegacy()->set2FAAuthenticated();

        // Billing return url
        $returnUrl = $this->addUrlParams($request->return_url, [
            'subscription_uid' => $subscription->uid,
        ]);
        \Acelle\Library\Facades\Billing::setReturnUrl($returnUrl);

        // checkout url
        return redirect()->away($request->checkout_url);
    }

    public function addUrlParams($url, $params)
    {
        // Parse the URL into components
        $parsed_url = parse_url($url);

        // Extract the existing query parameters (if any)
        $existing_params = [];
        if (isset($parsed_url['query'])) {
            parse_str($parsed_url['query'], $existing_params);
        }

        // Merge existing and new parameters
        $merged_params = array_merge($existing_params, $params);

        // Build the new query string
        $new_query = http_build_query($merged_params);

        // Rebuild the URL with the new query string
        $new_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

        // Include the port if specified
        if (isset($parsed_url['port'])) {
            $new_url .= ':' . $parsed_url['port'];
        }

        // Include the path if specified
        if (isset($parsed_url['path'])) {
            $new_url .= $parsed_url['path'];
        }

        // Append the new query string
        if ($new_query) {
            $new_url .= '?' . $new_query;
        }

        return $new_url;
    }
}
