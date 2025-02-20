@extends('layouts.core.frontend_no_subscription', [
	'menu' => 'subscription',
])

@section('title', trans('messages.subscription'))

@section('head')
    <script type="text/javascript" src="{{ AppUrl::asset('core/js/group-manager.js') }}"></script>
@endsection

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
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

    <h3 class="fw-semibold">{{ trans('messages.invoice') }}: #{{ $invoice->number }}</h3>

    <div class="row my-3" style="font-family: Arial, sans-serif;">
        <div class="col-lg-8 col-md-10">
            @if ($invoice->isPaid())
                <div class="alert alert-success alert-center text-center rounded-3 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-1" style="font-size: 1.5rem;">{{ trans('messages.invoice.invoice_is_paid') }}</h5>
                    <p class="mb-0" style="font-size: 1rem;">{{ trans('messages.invoice.no_payment_needed') }}</p>
                </div>
            @endif

            <div class="card border shadow-sm rounded-3 mb-4" style="border-left: 5px solid #007bff;">
                <div class="card-body p-4">
                    <div class="row">
                        <div class="col-6">
                            <h6 class="fw-bold" style="font-size: 1.1rem; color: #333;">{{ $bill['billing_first_name'] }} {{ $bill['billing_last_name'] }}</h6>
                            <p class="mb-1 text-muted">{{ $bill['billing_address'] }}, {{ $bill['billing_country'] }}</p>
                            <p class="mb-1 text-muted">{{ $bill['billing_email'] }}</p>
                            <p class="text-muted mb-0">{{ $bill['billing_phone'] }}</p>
                        </div>
                        <div class="col-6 text-end">
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr>
                                        <td class="text-end fw-semibold text-muted">{{ trans('messages.invoice') }} #:</td>
                                        <td style="font-size: 1rem;">{{ $invoice->number }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-end fw-semibold text-muted">{{ trans('messages.created_at') }}:</td>
                                        <td>{{ $invoice->customer->formatCurrentDateTime('datetime_full') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-end fw-semibold text-muted">{{ trans('messages.due_date') }}:</td>
                                        <td>{{ $invoice->customer->formatDateTime($bill['due_date'], 'datetime_full') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <table class="table table-hover table-bordered shadow-sm rounded-3 mb-4" style="border-color: #e0e0e0;">
                <thead style="background-color: #f1f3f5;">
                    <tr>
                        <th style="font-size: 1rem;">{{ trans('messages.invoice.items') }}</th>
                        <th class="text-end" style="font-size: 1rem;">{{ trans('messages.invoice.price') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($bill['bill'] as $item)
                        <tr>
                            <td>
                                <p class="fw-bold fst-italic mb-0 text-dark" style="font-size: 1.05rem;">{{ $item['title'] }}</p>
                                <small class="text-muted">{!! $item['description'] !!}</small>
                            </td>
                            <td class="text-end" style="font-size: 1rem; color: #555;">{{ $item['price'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot style="background-color: #f8f9fa;">
                    <tr>
                        <td class="text-end fw-semibold text-muted">{{ trans('messages.bill.subtotal') }}:</td>
                        <td class="text-end" style="color: #333;">{{ $bill['sub_total'] }}</td>
                    </tr>
                    <tr>
                        <td class="text-end fw-semibold text-muted">{{ trans('messages.bill.tax') }}:</td>
                        <td class="text-end" style="color: #333;">{{ $bill['tax'] }}</td>
                    </tr>
                    <tr>
                        <td class="text-end fw-bold" style="font-size: 1.1rem;">{{ trans('messages.bill.total') }}:</td>
                        <td class="text-end fw-bold" style="font-size: 1.1rem;">{{ $bill['total'] }}</td>
                    </tr>
                </tfoot>
            </table>

            <div class="text-end">
                @if ($invoice->isPaid())
                    <a class="btn btn-light btn-icon text-nowrap xtooltip" title="{{ trans('messages.download') }}" target="_blank" href="{{ action('InvoiceController@download', [
                        'uid' => $invoice->uid,
                    ]) }}">
                        <i class="material-symbols-rounded me-1">download</i>{{ trans('messages.download') }}
                    </a>
                @endif
            </div>
        </div>
    </div>
@endsection