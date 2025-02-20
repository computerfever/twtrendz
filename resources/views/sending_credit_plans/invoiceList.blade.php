@if ($invoices->count() > 0)
	<table class="table table-box pml-table table-log"
		current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
	>
		@foreach ($invoices as $key => $invoice)
            @php
                $billInfo = $invoice->mapType()->getBillingInfo();
            @endphp
			<tr>
                <td width="1%">
					@switch($invoice->status)
						@case(Acelle\Model\Invoice::STATUS_PAID)
							<i class="material-symbols-rounded me-2 text-success invoice-list-icon">credit_score</i>
							@break
						@case(Acelle\Model\Invoice::STATUS_NEW)
							<i class="material-symbols-rounded me-2 text-warning invoice-list-icon">add_circle_outline</i>
							@break
						@default
							<i class="material-symbols-rounded me-2 text-muted invoice-list-icon">remove_circle_outline</i>
					@endswitch
				</td>
                <td>
                    <span class="no-margin kq_search font-weight-semibold text-nowrap">
                        #{{ $invoice->uid }}
                    </span>
                    <div>
                        <span class="text-muted2">{{ $invoice->description }}</span>
                    </div>
                </td>
                <td>
                    <span class="no-margin kq_search font-weight-semibold text-nowrap">
                        {{ Auth::user()->customer->formatDateTime($invoice->created_at, 'datetime_full') }}
                    </span>
                    <div>
                        <span class="text-muted2">{{ trans('messages.created_at') }}</span>
                    </div>
                </td>
                <td>
                    <span class="no-margin kq_search font-weight-semibold text-nowrap">
                        {{ $billInfo['total'] }}
                    </span>
                    <div>
                        <span class="text-muted2">{{ trans('messages.invoice.amount') }}</span>
                    </div>
                </td>
                <td>
                    <span class="no-margin kq_search font-weight-semibold" style="text-transform: capitalize;">
                        {{ number_with_delimiter($invoice->sending_credits) }}
                    </span>
                    <div>
                        <span class="text-muted2">{{ trans('messages.sending_credit_plan.credits') }}</span>
                    </div>
                </td>
                <td>
                    @if ($invoice->getPendingTransaction())
                        <span class="no-margin kq_search">
                            <span class="label bg-pending" style="white-space: nowrap;">
                                {{ trans('messages.invoice.status.pending') }}
                            </span>
                        </span>
                    @else
                        <span class="no-margin kq_search">
                            <span class="label bg-{{ $invoice->status }}" style="white-space: nowrap;">
                                {{ trans('messages.invoice.status.' . $invoice->status) }}
                            </span>
                        </span>
                    @endif
                </td>
				<td class="text-end text-nowrap">
					<div class="btn-group">
                        @if ($invoice->isNew() && !$invoice->getPendingTransaction())
                            <a href="{{ action('CheckoutController@billingAddress', [
                                'invoice_uid' => $invoice->uid,
                            ]) }}" class="btn btn-secondary me-2">
                                <span class="material-symbols-rounded">shopping_cart</span>
                                {{ trans('messages.sending_credit_plan.checkout') }}
                            </a>
                        @endif
                        <button role="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            @if (\Auth::user()->customer->can('download', $invoice))
                                <li>
                                    <a class="dropdown-item" href="{{ action('InvoiceController@download', [
                                        'uid' => $invoice->uid,
                                    ]) }}">
                                        <i class="material-symbols-rounded me-1">download</i>
                                        {{ trans('messages.invoice.dowload_invoice') }}
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a class="dropdown-item"
                                    list-action="logs"
                                    href="{{ action('InvoiceController@logs', [
                                        'invoice_uid' => $invoice->uid
                                    ]) }}"
                                >
                                    <i class="material-symbols-rounded me-1">history</i>
                                    {{ trans('messages.invoice.logs') }}
                                </a>
                            </li>
                            @if (\Auth::user()->customer->can('delete', $invoice))
                                <li>
                                    <a class="dropdown-item list-action-single" link-method="DELETE" link-confirm="{{ trans('messages.invoice.delete.confirm') }}"
                                        href="{{ action('InvoiceController@delete', ['invoice_uid' => $invoice->uid]) }}">
                                            <i class="material-symbols-rounded me-1">delete</i>
                                            {{ trans('messages.invoice.delete') }}
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </div>
                </td>
			</tr>
            @if ($invoice->lastTransactionIsFailed())
                <tr class="bg-light">
                    <td colspan="7" class=" pe-2">
                        @include('elements._notification', [
                            'level' => 'danger',
                            'message' => $invoice->lastTransaction()->error
                        ])
                    </td>
                </tr>
            @endif
		@endforeach
	</table>

	@include('elements/_per_page_select', ["items" => $invoices])

    <script>
        $(function() {
            $('[list-action="reject"]').on('click', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');

                InvoiceList.showPopup(url);
            });

            $('[list-action="logs"]').on('click', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');

                InvoiceList.showPopup(url);
            });
        });

        var InvoiceList = {
            popup: null,

            showPopup: function(url) {
                this.popup = new Popup();
                this.popup.load(url);
            }
        }
    </script>

@elseif (!empty(request()->keyword) || !empty(request()->filters))
	<div class="empty-list">
		<span class="material-symbols-rounded">assignment_turned_in</span>
		<span class="line-1">
			{{ trans('messages.no_search_result') }}
		</span>
	</div>
@else
	<div class="empty-list">
		<span class="material-symbols-rounded">assignment_turned_in</span>
		<span class="line-1">
			{{ trans('messages.invoice.empty') }}
		</span>
	</div>
@endif
