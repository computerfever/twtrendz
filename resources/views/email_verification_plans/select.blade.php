@extends('layouts.popup.large')

@section('content')
    <div class="sub-section">
                    
        <h2>{{ trans('messages.email_verification_plan.select_plan') }}</h2>
        <p>{{ trans('messages.email_verification_plan.select_plan.wording') }}</p>

        <div class="new-price-box" style="margin-right: -30px">
            <div class="">
                @foreach ($plans as $plan)
                    <div
                        class="new-price-item mb-3 d-inline-block plan-item showed"
                        style="width: calc(33.3% - 20px)"
                    >
                        <div style="height: 100px">
                            <div class="price">
                                {!! format_price($plan->getPrice(), $plan->currency->format, true) !!}
                                <span class="p-currency-code">{{ $plan->currency->code }}</span>
                            </div>
                            <p>
                                <span class="material-symbols-rounded text-muted2 me-1">add_task</span>
                                <span class="fw-semibold">
                                    {{ trans('messages.email_verification_credits.count', [
                                        'number' => number_with_delimiter($plan->credits)
                                    ]) }}
                                </span>
                            </p>
                        </div>
                        <hr class="mb-2" style="width: 40px">
                        <div>
                            <label class="plan-title fs-5 fw-600 mt-0">{{ $plan->name }}</label>
                        </div>

                        <div style="height: 100px">
                            <p class="mt-4">{{ $plan->description }}</p>
                        </div>

                        <a
                            link-method="POST"
                            href="{{ action("EmailVerificationPlanController@buy", [
                                'plan_uid' => $plan->uid,
                            ]) }}"
                            class="btn btn-primary rounded-3 d-block mt-4 shadow-sm">
                                {{ trans('messages.plan.select') }}
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
                
    </div>
@endsection