@if ($items->count() > 0)
	<table class="table table-box pml-table table-log"
		current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
	>
		<tr>
			<th>{{ trans('messages.recipient') }}</th>
			<th>{{ trans('messages.url') }}</th>
			<th>{{ trans('messages.ip_address') }}</th>
			<th>{{ trans('messages.campaign') }}</th>
			<th>{{ trans('messages.sending_server') }}</th>
			<th>{{ trans('messages.created_at') }}</th>
			<th></th>
		</tr>
		@foreach ($items as $key => $item)
			<tr>
				<td>
					<span class="no-margin kq_search">{{ $item->trackingLog->subscriber->email }}</span>
					<span class="text-muted second-line-mobile">{{ trans('messages.recipient') }}</span>
				</td>
				<td>
					<span class="no-margin kq_search url-truncate">
						<a title="{{ $item->url }}" href="{{ $item->url }}" target="_blank">
							{{ $item->url }}
						</a>
					</span>
					<span class="text-muted second-line-mobile">{{ trans('messages.url') }}</span>
				</td>
				<td>
					<span class="no-margin kq_search">{{ $item->ip_address }}</span>
					<span class="text-muted second-line-mobile">{{ trans('messages.ip_address') }}</span>
				</td>
				<td>
					<span class="no-margin kq_search">{{ is_null($item->trackingLog->campaign) ? 'N/A' : $item->trackingLog->campaign->name }}</span>
					<span class="text-muted second-line-mobile">{{ trans('messages.campaign') }}</span>
				</td>
				<td>
					<span class="no-margin kq_search">{{ $item->trackingLog->getSendingServer() ? $item->trackingLog->getSendingServer()->name : '[server]'}}</span>
					<span class="text-muted second-line-mobile">{{ trans('messages.sending_server') }}</span>
				</td>
				<td>
					<span class="no-margin kq_search">{{ Auth::user()->customer->formatDateTime($item->created_at, 'datetime_full') }}</span>
					<span class="text-muted second-line-mobile">{{ trans('messages.created_at') }}</span>
				</td>
				@if ($item->trackingLog->campaign->campaignWebhooks()->click()->count())
                    <td class="text-end">
                        <a href="{{ action('CampaignController@clickLogExecute', [
                            'uid' => $item->trackingLog->campaign->uid,
                            'message_id' => $item->message_id,
                        ]) }}" class="btn btn-light click_log_detail">
                            {{ trans('messages.webhook.execute_click_callback') }}
                        </a>                   
                    </td>
                @endif
			</tr>
		@endforeach
	</table>
	@include('elements/_per_page_select')

	<script>
		var ClickLogList = {
			webhookPopup: null,

			getWebhookPopup: function(url) {
				if (this.webhookPopup == null) {
					this.webhookPopup = new Popup({
						url: url
					});
				}

				return this.webhookPopup;
			}
		}

		$(function() {
			$('.click_log_detail').on('click', function(e) {
				e.preventDefault();

				var url = $(this).attr('href');

				ClickLogList.getWebhookPopup(url).load();
			});
		})
	</script>
	
@elseif (!empty(request()->keyword) || !empty(request()->filters["campaign_uid"]))
	<div class="empty-list">
		<span class="material-symbols-rounded">auto_awesome</span>
		<span class="line-1">
			{{ trans('messages.no_search_result') }}
		</span>
	</div>
@else					
	<div class="empty-list">
		<span class="material-symbols-rounded">auto_awesome</span>
		<span class="line-1">
			{{ trans('messages.log_empty_line_1') }}
		</span>
	</div>
@endif