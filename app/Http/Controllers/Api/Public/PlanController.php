<?php

namespace Acelle\Http\Controllers\Api\Public;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;

/**
 * /api/v1/plans - API controller for managing plans.
 */
class PlanController extends Controller
{
    public function availablePlans()
    {

        $plans = \Acelle\Model\PlanGeneral::getAvailableGeneralPlans();

        $plans = $plans->map(function ($plan) {
            return [
                'uid' => $plan->uid,
                'name' => $plan->name,
                'price' => $plan->price,
                'currency_code' => $plan->currency->code,
                'frequency_amount' => $plan->frequency_amount,
                'frequency_unit' => $plan->frequency_unit,
                'options' => $plan->getOptions(),
                'status' => $plan->status,
                'quota' => $plan->quota,
                'created_at' => $plan->created_at,
                'updated_at' => $plan->updated_at,
            ];
        });

        return \Response::json($plans, 200);
    }
}
