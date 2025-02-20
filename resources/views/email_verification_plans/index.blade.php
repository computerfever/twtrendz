@extends('layouts.core.frontend_no_subscription', [
	'menu' => 'subscription',
])

@section('title', trans('messages.subscription'))

@section('head')
    <script type="text/javascript" src="{{ AppUrl::asset('core/js/group-manager.js') }}"></script>

    <script type="text/javascript" src="{{ URL::asset('core/echarts/echarts.min.js') }}"></script>
    <script type="text/javascript" src="{{ URL::asset('core/echarts/dark.js') }}"></script> 
@endsection

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('messages.subscription') }}</li>
        </ul>
        <h1>
            <span class="text-semibold">{{ Auth::user()->customer->displayName() }}</span>
        </h1>
    </div>

@endsection

@section('content')

    @include("account._menu", [
		'menu' => 'subscription',
	])

    @include("subscription._menu", [
        'menu' => 'email_verification_plan',
    ])

    <div class="d-flex">
        <div class="me-auto pe-5">
            <h2>{{ trans('messages.email_verification_credit.overview') }}</h2>
            <p>{{ trans('messages.email_verification_plan.wording') }}</p>
        </div>
        <div class="text-end d-none">
            <a data-action="buy-credit"
                href="{{ action("EmailVerificationPlanController@select") }}" role="button"
                class="btn btn-primary text-nowrap"
            >
                <span class="material-symbols-rounded">add</span> {{ trans('messages.email_verification_plan.buy_more') }}
            </a>
        </div>
    </div>

    <div class="stats-boxes">
        <div class="stats-box px-4" style="width:33.3%">
            <div class="d-flex">
                <h3 class="mb-1">
                    @if (Auth::user()->customer->getCurrentActiveSubscription()->getVerifyEmailCreditTracker()->getRemainingCredits() == '-1')
                        {{ trans('messages.unlimited') }}
                    @else
                        {{ number_with_delimiter(Auth::user()->customer->getCurrentActiveSubscription()->getVerifyEmailCreditTracker()->getRemainingCredits()) }}
                    @endif
                </h3>
            </div>
            
            {{-- <p>{{ trans('messages.email_verification_plan.credits') }}</p> --}}
            <div class="text-start">
                <a data-action="buy-credit"
                    href="{{ action("EmailVerificationPlanController@select") }}" role="button"
                    class="btn btn-primary text-nowrap mt-2 @if (Auth::user()->customer->getCurrentActiveSubscription()->getVerifyEmailCreditTracker()->getRemainingCredits() == '-1') disabled pe-none @endif"
                >
                    <span class="material-symbols-rounded me-1">add</span> {{ trans('messages.email_verification_plan.buy_more') }}
                </a>
            </div>
        </div>
        <div class="stats-box px-4" style="width:33.3%">
            @if (Auth::user()->customer->getLastPaidEmailVerificationCreditsInvoice())
                <h3>
                    <span class="material-symbols-rounded text-success me-2">
                        add_task
                    </span>
                    {{ number_with_delimiter(number_with_delimiter(Auth::user()->customer->getLastPaidEmailVerificationCreditsInvoice()->email_verification_credits)) }}
                    {{-- {{ trans('messages.email_verification_credits.count', [
                        'number' => number_with_delimiter(number_with_delimiter(Auth::user()->customer->getLastPaidEmailVerificationCreditsInvoice()->email_verification_credits))
                    ]) }} --}}
                </h3>
                <p class="mb-0">
                    {{ trans('messages.invoice.last_purchase') }}
                </p>
                <p>
                    (
                        <span class="text-semibold">{{ format_price(Auth::user()->customer->getLastPaidEmailVerificationCreditsInvoice()->total(), Auth::user()->customer->getLastPaidEmailVerificationCreditsInvoice()->currency->format) }}</span>
                        ;
                        {{ Auth::user()->customer->formatDateTime(Acelle\Model\Invoice::first()->created_at, 'datetime_full') }}
                    )
                </p>
            @else
                <h3>
                    --
                </h3>
                <p>{{ trans('messages.invoice.last_purchase') }}
                </p>
            @endif
        </div>
        <div class="stats-box px-2 py-3 d-none" style="width:33.3%">
            {{-- <h3>
                {{ Auth::user()->customer->emailVerificationCreditsInvoices()->count() }}
            </h3>
            <p>{{ trans('messages.invoices') }}</p> --}}

            <div>
                <div class="chart has-fixed-height-250"
                    id="QuotaChart"
                    style="width: 100%;height:150px;"
                ></div>
                
                <script>
                    $(function() {
                        QuotaChart.showChart();
                    });
                    var QuotaChart = {
                        url: '',
                        getChart: function() {
                            return $('#QuotaChart');
                        },
                
                        showChart: function() {
                            $.ajax({
                                method: "GET",
                                url: this.url,
                            })
                            .done(function( response ) {
                                QuotaChart.renderChart( response.data );
                            });
                        },
                
                        renderChart: function(data) {
                            var chart = echarts.init(QuotaChart.getChart()[0], ECHARTS_THEME);
                            
                            option = {
                                tooltip: {
                                    trigger: 'axis',
                                    axisPointer: {
                                        type: 'cross',
                                        label: {
                                            backgroundColor: '#6a7985'
                                        }
                                    }
                                },
                                legend: {
                                    data: ['Credits', 'Used']
                                },
                                grid: {
                                    left: '3%',
                                    right: '4%',
                                    bottom: '3%',
                                    containLabel: true
                                },
                                xAxis: [
                                    {
                                    type: 'category',
                                    boundaryGap: false,
                                    data: ['Jan', 'Feb', 'Mar', 'Apr', 'Aug']
                                    }
                                ],
                                yAxis: [
                                    {
                                    type: 'value'
                                    }
                                ],
                                series: [
                                    {
                                        name: 'Credits',
                                        type: 'line',
                                        stack: 'Total',
                                        areaStyle: {},
                                        emphasis: {
                                            focus: 'series'
                                        },
                                        data: [1000, 1000, 2000, 2000, 2000]
                                    },
                                    {
                                        name: 'Used',
                                        type: 'line',
                                        stack: 'Total',
                                        areaStyle: {},
                                        emphasis: {
                                            focus: 'series'
                                        },
                                        data: [220, 330, 401, 1200, 1700]
                                    }
                                ]
                            };

                            // use configuration item and data specified to show chart
                            chart.setOption(option);
                        }
                    }    
                </script>                
            </div>
        </div>
    </div>


    <div class="sub-section">
        <div class="row">
            <div class="col-sm-12 col-md-12 col-lg-12">
                <ul class="nav nav-tabs nav-underline mb-1" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="javascript:;" data-bs-toggle="tab" data-bs-target="#nav-invoices">
                            {{ trans('messages.invoices') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#nav-transactions" data-bs-toggle="tab">
                            {{ trans('messages.transactions') }}
                        </a>
                    </li>
                </ul>
    
                <div class="tab-content">
                    <div id="nav-invoices" class="tab-pane fade in show active">
                        <form id="invoiceListContainer" class="listing-form"
                            per-page="15"
                        >
                            <div class="d-flex top-list-controls top-sticky-content">
                                <div class="me-auto">
                                    <div class="filter-box">
                                        <span class="filter-group">
                                            <select class="select" name="sort_order">
                                                <option value="invoices.created_at">{{ trans('messages.created_at') }}</option>
                                            </select>
                                            <input type="hidden" name="sort_direction" value="desc" />
                    <button type="button" class="btn btn-light sort-direction" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                                                <span class="material-symbols-rounded desc">sort</span>
                                            </button>
                                        </span>
                                        <span class="filter-group">
                                            <select class="select" name="status">
                                                <option value="">{{ trans('messages.invoice.all_statuses') }}</option>
                                                <option value="pending">{{ trans('messages.invoice.status.pending') }}</option>
                                                <option value="{{ Acelle\Model\Invoice::STATUS_NEW }}">{{ trans('messages.invoice.status.new') }}</option>
                                                <option value="{{ Acelle\Model\Invoice::STATUS_PAID }}">{{ trans('messages.invoice.status.paid') }}</option>
                                            </select>
                                        </span>
                                    </div>
                                </div>
                            </div>
    
                            <div id="invoiceList">
                            </div>
                        </form>
    
                        <script>
                            var InvoiceList = {
                                getList: function() {
                                    return makeList({
                                        url: '{{ action('EmailVerificationPlanController@invoiceList', [
                                            'show_customer' => true,
                                        ]) }}',
                                        container: $('#invoiceListContainer'),
                                        content: $('#invoiceList')
                                    });
                                }
                            };
    
                            $(document).ready(function() {
                                InvoiceList.getList().load();
                            });
                        </script>
                    </div>
                    <div id="nav-transactions" class="tab-pane fade">
                        <form id="transactionListContainer" class="listing-form"
                            per-page="15"
                        >
                            <div class="d-flex top-list-controls top-sticky-content">
                                <div class="me-auto">
                                    <div class="filter-box">
                                        <span class="filter-group">
                                            <select class="select" name="sort_order">
                                                <option value="invoices.created_at">{{ trans('messages.created_at') }}</option>
                                            </select>
                                            <input type="hidden" name="sort_direction" value="desc" />
                    <button type="button" class="btn btn-light sort-direction" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                                                <span class="material-symbols-rounded desc">sort</span>
                                            </button>
                                        </span>
                                        <span class="filter-group">
                                            <select class="select" name="status">
                                                <option value="">{{ trans('messages.transaction.all_statuses') }}</option>
                                                <option value="{{ Acelle\Model\Transaction::STATUS_PENDING }}">{{ trans('messages.transaction.status.pending') }}</option>
                                                <option value="{{ Acelle\Model\Transaction::STATUS_SUCCESS }}">{{ trans('messages.transaction.status.success') }}</option>
                                                <option value="{{ Acelle\Model\Transaction::STATUS_FAILED }}">{{ trans('messages.transaction.status.failed') }}</option>
                                            </select>
                                        </span>
                                    </div>
                                </div>
                            </div>
    
                            <div id="TransactionList">
                            </div>
                        </form>
    
                        <script>
                            var TransactionList = {
                                getList: function() {
                                    return makeList({
                                        url: '{{ action('EmailVerificationPlanController@transactionList') }}',
                                        container: $('#transactionListContainer'),
                                        content: $('#TransactionList')
                                    });
                                }
                            };
    
                            $(document).ready(function() {
                                TransactionList.getList().load();
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function() {
            // Select plan popup
            new SelectPlanPopup({
                button: $('[data-action="buy-credit"]'),
                url: $('[data-action="buy-credit"]').attr('href'),
            });
        });

        var SelectPlanPopup = class {
            constructor(options) {
                this.button = options.button;
                this.url = options.url;
                this.popup = new Popup({
                    url: this.url,
                });
                
                //
                this.events();
            }

            events() {
                var _this = this;
                this.button.on('click', function(e) {
                    e.preventDefault();

                    _this.popup.load();
                })
            }
        }
    </script>

@endsection
