<?php

namespace Acelle\Http\Controllers\Api\Public;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;

/**
 * /api/v1/payment - API controller for managing subscriptions.
 */
class PaymentController extends Controller
{
    /**
     * Get all payment.
     *
     * GET /api/v1/payment/list
     *
     * @return \Illuminate\Http\Response
     */
    public function list(Request $request)
    {
        $payments = collect(\Acelle\Library\Facades\Billing::getEnabledPaymentGateways())->map(function ($gateway) {
            return [
                'type' => $gateway->getType(),
                'name' => $gateway->getName(),
                'description' => $gateway->getShortDescription(),
            ];
        });

        return \Response::json($payments, 200);
    }
}
