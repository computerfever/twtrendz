@if ($blacklists->count() > 0)
	<table class="table table-box pml-table table-log mt-10"
		current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
	>
		<tr>
			<th>
				<div class="checkbox inline check_all_list">
					<label>
						<input type="checkbox" name="page_checked" class="styled check_all">
					</label>
				</div>
			</th>
			<th>{{ trans('messages.email') }}</th>
			<th>{{ trans('messages.created_at') }}</th>
			<th class="text-end">{{ trans('messages.action') }}</th>
		</tr>
		@foreach ($blacklists as $key => $blacklist)
			<tr>
				<td width="1%">
					<div class="checkbox inline me-1">
						<label>
							<input type="checkbox" class="node styled"
								name="uids[]"
								value="{{ $blacklist->id }}"
							/>
						</label>
					</div>
				</td>
				<td>
					<span class="no-margin kq_search"><a class="reason-link" href="{{ action('Admin\BlacklistController@reason', $blacklist->id) }}">{{ @$blacklist->email }}</a></span>
					<span class="text-muted second-line-mobile">
					<a class="reason-link" href="{{ action('Admin\BlacklistController@reason', $blacklist->id) }}">
						{{ trans('messages.email') }}
					</a></span>
				</td>
				<td>
					<span class="no-margin kq_search">{{ Auth::user()->admin->formatDateTime($blacklist->created_at, 'datetime_full') }}</span>
					<span class="text-muted second-line-mobile">{{ trans('messages.created_at') }}</span>
				</td>
				<td class="text-end">
					@if (Auth::user()->admin->can('delete', $blacklist))
						<a
							link-confirm="{{ trans('messages.remove_blacklist_confirm') }}"
							href="{{ action('Admin\BlacklistController@delete', ["uids" => $blacklist->id]) }}"
							class="btn btn-primary btn-xs bg-grey list-action-single"
							data-popup="tooltip" title="{{ trans('messages.remove_from_blacklist') }}"
						>
							{{ trans('messages.blacklist.remove') }}
						</a>
					@endif
				</td>
			</tr>
		@endforeach
	</table>
	@include('elements/_per_page_select', ["items" => $blacklists])

	<script>
		$('.reason-link').click(function(e) {
			e.preventDefault();

			var bp = new Popup();
			bp.load($(this).attr('href'));
		});
	</script>
@elseif (!empty(request()->keyword) || !empty(request()->filters["campaign_uid"]))
	<div class="empty-list">
		<span class="material-symbols-rounded">block</span>
		<span class="line-1">
			{{ trans('messages.no_search_result') }}
		</span>
	</div>
@else
	<div class="empty-list">
		<span class="material-symbols-rounded">block</span>
		<span class="line-1">
			{{ trans('messages.blacklist_empty_line_1') }}
		</span>
	</div>
@endif
