@if ($total > 0)
	<table class="table table-box pml-table table-sub"
		current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
	>
		@foreach ($subscribers as $key => $item)
			<tr>
				<td width="1%">
					<div class="text-nowrap">
						<div class="checkbox inline me-1">
							<label>
								<input type="checkbox" class="node styled"
									name="ids[]"
									value="{{ $item->id }}"
								/>
							</label>
						</div>
					</div>
				</td>
				<td>
					<div class="d-flex align-items-center">
						<div class="subscriber-avatar mt-1">
							<a
                                image-popup="link"
                                href="{{ (isSiteDemo() ? 'https://i.pravatar.cc/300?v=' . $key : action('SubscriberController@avatarOrigin',  $item->id)) }}">
								<img src="{{ (isSiteDemo() ? 'https://i.pravatar.cc/300?v=' . $key : action('SubscriberController@avatar',  $item->id)) }}" />
							</a>
						</div>
						<div class="no-margin text-bold">
							<a class="kq_search" href="{{ action('SubscriberController@edit', ['list_uid' => $list->uid ,'id' => $item->id]) }}">
								{{ $item->email }}
							</a>
							<br />
							<span class="label label-flat bg-{{ $item->status }}">{{ trans('messages.' . $item->status) }}</span>
							<span class="label label-flat bg-{{ $item->verification_status }}">{{ trans('messages.email_verification_result_' . $item->verification_status) }}</span>
						</div>
					</div>
				</td>

				@foreach ($fields as $field)
					<?php $value = $item->getValueByField($field); ?>
					<td>
						<span class="no-margin stat-num kq_search">{{ empty($value) ? "--" : $value }}</span>
						<br>
						<span class="text-muted2">{{ $field->label }}</span>
					</td>
				@endforeach

				@if (in_array("created_at", is_array(request()->columns) ? request()->columns : []))
					<td>
						<span class="no-margin stat-num">{{ Auth::user()->customer->formatDateTime($item->created_at, 'datetime_full') }}</span>
						<br>
						<span class="text-muted2">{{ trans('messages.created_at') }}</span>
					</td>
				@endif

				@if (in_array("updated_at", is_array(request()->columns) ? request()->columns : []))
					<td>
						<span class="no-margin stat-num">{{ Auth::user()->customer->formatDateTime($item->updated_at, 'datetime_full') }}</span>
						<br>
						<span class="text-muted2">{{ trans('messages.updated_at') }}</span>
					</td>
				@endif

				<td class="text-end text-nowrap pe-0">
					@if (\Gate::allows('update', $item))
						<a href="{{ action('SubscriberController@edit', ['list_uid' => $list->uid, "id" => $item->id]) }}" role="button" class="btn btn-secondary btn-icon">
							<span class="material-symbols-rounded">edit</span>
						</a>
					@endif
					<div class="btn-group">
						<button role="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"></button>
						<ul class="dropdown-menu dropdown-menu-end">
							@if (\Gate::allows('subscribe', $item))
								<li><a class="dropdown-item list-action-single"  link-method="POST" href="{{ action('SubscriberController@subscribe', ['list_uid' => $list->uid, "ids" => $item->id]) }}"><span class="material-symbols-rounded">mark_email_read</span> {{ trans('messages.subscribe') }}</a></li>
							@endif
							@if (\Gate::allows('unsubscribe', $item))
								<li><a class="dropdown-item list-action-single"  link-method="POST" href="{{ action('SubscriberController@unsubscribe', ['list_uid' => $list->uid, "ids" => $item->id]) }}"><span class="material-symbols-rounded">logout</span> {{ trans('messages.unsubscribe') }}</a></li>
							@endif

							<li>
								<a href="#copy" class="dropdown-item copy_move_subscriber"
									data-url="{{ action('SubscriberController@copyMoveForm', [
										'ids' => $item->id,
										'from_uid' => $list->uid,
										'action' => 'copy',
									]) }}">
										<span class="material-symbols-rounded">copy_all</span> {{ trans('messages.copy_to') }}
								</a>
							</li>
							<li>
								<a href="#move" class="dropdown-item copy_move_subscriber"
									data-url="{{ action('SubscriberController@copyMoveForm', [
										'ids' => $item->id,
										'from_uid' => $list->uid,
										'action' => 'move',
									]) }}">
									<span class="material-symbols-rounded">exit_to_app</span> {{ trans('messages.move_to') }}
								</a>
							</li>
							@if (\Gate::allows('update', $item))
								<li>
									<a class="dropdown-item list-action-single" link-method="POST" link-confirm="{{ trans('messages.subscribers.resend_confirmation_email.confirm') }}" href="{{ action('SubscriberController@resendConfirmationEmail', ['list_uid' => $list->uid, "ids" => $item->id]) }}">
										<span class="material-symbols-rounded">mark_email_read</span> {{ trans('messages.subscribers.resend_confirmation_email') }}
									</a>
								</li>
							@endif
							@if (\Gate::allows('delete', $item))
								<li><a link-method="POST" class="dropdown-item list-action-single" link-confirm="{{ trans('messages.delete_subscribers_confirm') }}" href="{{ action('SubscriberController@delete', ['list_uid' => $list->uid, "ids" => $item->id]) }}"><span class="material-symbols-rounded">delete_outline</span> {{ trans("messages.delete") }}</a></li>
							@endif
						</ul>
					</div>
				</td>

			</tr>
		@endforeach
	</table>
	@include('elements/_per_page_select', ["items" => $subscribers])
	
@elseif (!empty(request()->keyword))
	<div class="empty-list">
		<span class="material-symbols-rounded">people</span>
		<span class="line-1">
			{{ trans('messages.no_search_result') }}
		</span>
	</div>
@else
	<div class="empty-list">
		<span class="material-symbols-rounded">people</span>
		<span class="line-1">
			{{ trans('messages.subscriber_empty_line_1') }}
		</span>
	</div>
@endif
