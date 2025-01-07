@extends('layouts.core.frontend_dark', [
    'subscriptionPage' => true,
])

@section('title', trans('messages.subscriptions'))

@section('menu_title')
    @include('subscription._title')
@endsection

@section('menu_right')
    @if ($invoice->type !== \Acelle\Model\InvoiceNewSubscription::TYPE_NEW_SUBSCRIPTION)
        <li class="nav-item d-flex align-items-center">
            <a  href="{{ action('SubscriptionController@index') }}"
                class="nav-link py-3 lvl-1">
                <i class="material-symbols-rounded me-2">arrow_back</i>
                <span>{{ trans('messages.go_back') }}</span>
            </a>
        </li>
    @endif

    @include('layouts.core._top_activity_log')
    @include('layouts.core._menu_frontend_user', [
        'menu' => 'subscription',
    ])
@endsection

@section('content')
    <div class="container mt-4 pt-3 mb-5">
        <div class="row">
            <div class="col-md-8">
                <!-- display flash message -->
                @include('layouts.core._errors')

                @include('subscription._selectPlan')

                <div class="card mt-2 subscription-step">
                    <a href="" class="card-header py-3 select-plan-tab">
                        <div class="d-flex align-items-center">
                            <div class="me-3"><label class="subscription-step-number">2</label></div>
                            <div>
                                <h5 class="fw-600 mb-0 fs-6 text-start">
                                    {{ trans('messages.subscription.no_payment.confirmation') }}
                                </h5>
                                <p class="m-0 text-muted">{{ trans('messages.subscription.no_payment.confirmation.desc') }}</p>
                            </div>
                            <div class="ms-auto">
                                <span class="material-symbols-rounded fs-4 text-success">task_alt</span>
                            </div>
                        </div>
                    </a>
                    <div class="card-body py-4" style="padding-left: 72px;padding-right:72px">
                        <form class="billing-address-form" action="{{ action('SubscriptionController@noPaymentConfirmation', [
                            'invoice_uid' => $invoice->uid,
                        ]) }}"
                            method="POST">
                            {{ csrf_field() }}
                            
                            <p>{!! trans('messages.subscription.review_plan_click_proceed') !!}</p>

                            <div class="mt-2">
                                <button type="submit" class="btn btn-primary">{{ trans('messages.subscription.get_started') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="order-box" style="position: sticky;top: 80px;">

                </div>
            </div>
        </div>
    </div>

    <script>
        var SubscriptionBillingInfo = {
            orderBox: null,

            getOrderBox: function() {
                if (this.orderBox == null) {
                    this.orderBox = new Box($('.order-box'), '{{ action('SubscriptionController@orderBox', [
                        'invoice_uid' => $invoice->uid,
                    ]) }}');
                }
                return this.orderBox;
            }
        }

        $(function() {
            SubscriptionBillingInfo.getOrderBox().load();

            $('[name=same_as_contact]').change(function() {
                var checked = $(this).is(':checked');
                
                $.ajax({
                    url: '{{ action('AccountController@editBillingAddress') }}',
                    method: 'GET',
                    data: {
                        same_as_contact: checked
                    },
                    success: function (response) {
                        billingPopup.loadHtml(response);
                    }
                });
            });
        });
    </script>
@endsection