@if ($subscribers->count() > 0)
    <table class="table table-box pml-table table-sub"
        current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
    >
        @foreach ($subscribers as $key => $subscriber)

            <?php 

            $bounced_message = json_decode($subscriber->bounced_message);
            // $bounced_message = "<pre>".print_r(json_decode($subscriber->bounced_message), TRUE)."</pre>";
            $skipped_message = json_decode($subscriber->skipped_message);

            // echo $skipped_message;

            // echo htmlentities($skipped_message);
            // print_r(@$subscriber);
            // exit();

            ?>
            <tr class="position-relative">
                <td width="1%" class="list-check-col">
                    <div class="text-nowrap">
                        <div class="checkbox inline me-1">
                            <label>
                                <input type="checkbox" class="node styled"
                                    name="ids[]"
                                    value="{{ $subscriber->id }}"
                                />
                            </label>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="no-margin text-bold">
                        @if (\Gate::allows('update', $subscriber))
                        <a class="kq_search" href="{{ action('SubscriberController@edit', ['list_uid' => $list->uid ,'id' => $subscriber->id]) }}">
                        @else
                        <a class="kq_search" href="#">
                        @endif
                            {{ $subscriber->email }}
                            
                            @if(!empty($bounced_message))
                                <span class="label label-flat bg-danger">
                                    {{@$bounced_message->category}}
                                    {{@$bounced_message->bounce_classification}}
                                </span>
                            @else
                                <span class="label label-flat bg-{{ $subscriber->status }}">{{ trans('messages.' . $subscriber->status) }}</span>
                            @endif

                            <span class="label label-flat bg-{{ $subscriber->verification_status }}">{{ trans('messages.email_verification_result_' . $subscriber->verification_status) }}</span>
                        </a>
                        <br />
                        <span data-popup="tooltip" title='
                            <pre> @if ($subscriber->bounced_message)
                                {{print_r($bounced_message,true)}}
                            @elseif ($subscriber->feedback_message)
                                {{ $subscriber->feedback_message; }}
                            @elseif($subscriber->failed_message)
                                {{ $subscriber->failed_message }}
                            @elseif($subscriber->skipped_message)
                                {{ print_r($skipped_message,true) }}
                            @elseif($subscriber->new_message)
                                {{ $subscriber->new_message }}
                            @endif </pre>
                        ' class="label label-flat bg-{{ $subscriber->delivery_status }} kq_search">
                        {{-- {{$subscriber->delivery_status}} --}}
                        {{ trans('messages.tracking_log_status_' . $subscriber->delivery_status) }}
                        </span>
                    </div>
                </td>

                @foreach ($fields as $field)
                    <td>
                        <span class="no-margin stat-num kq_search">{{ empty($subscriber->getValueByField($field)) ? "--" : $subscriber->getValueByField($field) }}</span>
                        <br>
                        <span class="text-muted2">{{ $field->label }}</span>
                    </td>
                @endforeach

                @if (in_array("created_at", is_array(request()->columns) ? request()->columns : []))
                    <td>
                        <span class="no-margin stat-num">{{ Auth::user()->customer->formatDateTime($subscriber->created_at, 'datetime_full') }}</span>
                        <br>
                        <span class="text-muted2">{{ trans('messages.created_at') }}</span>
                    </td>
                @endif

                @if (in_array("updated_at", is_array(request()->columns) ? request()->columns : []))
                    <td>
                        <span class="no-margin stat-num">{{ Auth::user()->customer->formatDateTime($subscriber->updated_at, 'datetime_full') }}</span>
                        <br>
                        <span class="text-muted2">{{ trans('messages.updated_at') }}</span>
                    </td>
                @endif

                <td>
                    <span class="no-margin stat-num">{{ null !== $subscriber->lastOpenLog($campaign) ? Auth::user()->customer->formatDateTime($subscriber->lastOpenLog($campaign)->created_at, 'datetime_full') : "--" }}</span>
                    <br>
                    <span class="text-muted2">{{ trans('messages.last_open') }}</span>
                    @if (null !== $subscriber->lastOpenLog($campaign))
                        <a href="{{ action('CampaignController@openLog', ["uid" => $campaign->uid, "search_keyword" => $subscriber->email]) }}">
                            {{ $subscriber->openLogs($campaign)->count() . " " . Tool::getPluralPrase(trans("messages.time"), $subscriber->openLogs($campaign)->count()) }}</a>
                    @endif
                </td>

                <td>
                    <span class="no-margin stat-num">{{ null !== $subscriber->lastClickLog($campaign) ? Auth::user()->customer->formatDateTime($subscriber->lastClickLog($campaign)->created_at, 'datetime_full') : "--" }}</span>
                    <br>
                    <span class="text-muted2">{{ trans('messages.last_click') }}</span>
                    @if (null !== $subscriber->lastClickLog($campaign))
                        <a href="{{ action('CampaignController@clickLog', ["uid" => $campaign->uid, "search_keyword" => $subscriber->email]) }}">
                            {{ $subscriber->clickLogs($campaign)->count() . " " . Tool::getPluralPrase("time", $subscriber->clickLogs($campaign)->count()) }}
                        </a>
                    @endif
                </td>

                <td class="text-end text-nowrap pe-0">
                    @if (\Gate::allows('update', $subscriber))
                    <a href="{{ action('SubscriberController@edit', ['list_uid' => $list->uid, "id" => $subscriber->id]) }}" role="button" class="btn btn-secondary btn-icon">
                        <span class="material-symbols-rounded">edit</span>
                    </a>
					@endif
					<div class="btn-group">
						<button role="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"></button>
						<ul class="dropdown-menu dropdown-menu-end">
							@if (\Gate::allows('subscribe', $subscriber))
								<li><a class="dropdown-item list-action-single"
                                    link-method="POST"
                                    href="{{ action('SubscriberController@subscribe', ['list_uid' => $list->uid, "ids" => $subscriber->id]) }}"><span class="material-symbols-rounded">mark_email_read</span> {{ trans('messages.subscribe') }}</a></li>
							@endif
							@if (\Gate::allows('unsubscribe', $subscriber))
								<li><a class="dropdown-item list-action-single"  link-method="POST" href="{{ action('SubscriberController@unsubscribe', ['list_uid' => $list->uid, "ids" => $subscriber->id]) }}"><span class="material-symbols-rounded">logout</span> {{ trans('messages.unsubscribe') }}</a></li>
							@endif

							<li>
								<a href="#copy" class="dropdown-item copy_move_subscriber"
									data-url="{{ action('SubscriberController@copyMoveForm', [
										'ids' => $subscriber->id,
										'from_uid' => $list->uid,
										'action' => 'copy',
									]) }}">
										<span class="material-symbols-rounded">copy_all</span> {{ trans('messages.copy_to') }}
								</a>
							</li>
							<li>
								<a href="#move" class="dropdown-item copy_move_subscriber"
									data-url="{{ action('SubscriberController@copyMoveForm', [
										'ids' => $subscriber->id,
										'from_uid' => $list->uid,
										'action' => 'move',
									]) }}">
									<span class="material-symbols-rounded">exit_to_app</span> {{ trans('messages.move_to') }}
								</a>
							</li>
							@if (\Gate::allows('update', $subscriber))
								<li>
									<a class="dropdown-item list-action-single"
                                    link-method="POST"
                                    link-confirm="{{ trans('messages.subscribers.resend_confirmation_email.confirm') }}"
                                    href="{{ action('SubscriberController@resendConfirmationEmail', ['list_uid' => $list->uid, "ids" => $subscriber->id]) }}">
										<span class="material-symbols-rounded">mark_email_read</span> {{ trans('messages.subscribers.resend_confirmation_email') }}
									</a>
								</li>
							@endif
							@if (\Gate::allows('delete', $subscriber))
								<li><a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.delete_subscribers_confirm') }}" href="{{ action('SubscriberController@delete', ['list_uid' => $list->uid, "ids" => $subscriber->id]) }}"><span class="material-symbols-rounded">delete_outline</span> {{ trans("messages.delete") }}</a></li>
							@endif	
						</ul>
					</div>
                </td>

            </tr>
        @endforeach
    </table>
    @include('elements/_per_page_select', ["items" => $subscribers])
    
@elseif (!empty(request()->keyword) || !empty(request()->filters))
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
