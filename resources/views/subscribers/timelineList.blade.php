@if ($timelines->count() > 0)
    <label class="date small font-weight-semibold mb-0 divider">{{ trans('messages.automation.profile_timeline_head') }}</label>   
    @foreach($timelines as $timeline)
        @if ($timeline->activity)
            <div class="activity d-flex py-3 px-3">
                <div class="activity-media pr-4 text-center">
                    <div class="mb-1">
                        <span class="activity-icon border p-2 bg-light d-inline-block">
                            <span class="material-symbols-rounded">
                                history_toggle_off
                            </span>
                        </span>
                    </div>
                    <time class="d-block text-center mini mb-0">
                        @if ($timeline->created_at->greaterThan(Carbon\Carbon::now()->subDay(1)))
                            {{ $timeline->created_at->diffForHumans() }}
                        @else
                            {{ Auth::user()->customer->formatDateTime($timeline->created_at, 'datetime_full') }}
                        @endif
                    </time>
                </div>
                <div class="w-100">
                    <action class="d-block mb-2">
                        {{ $timeline->activity }}
                    </action>
                    <div class="d-flex align-items-center">
                        <div class="d-block small text-muted2">
                            <div class="d-flex align-items-center">
                                <span class="material-symbols-rounded me-1">
                                    bookmark_border
                                </span>
                                <span>{{ trans('messages.timeline.type.automation') }}</span>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        @else

            <div class="activity d-flex py-3 px-3">
                <div class="activity-media pr-4 text-center">
                    <div class="mb-1">
                        <span class="activity-icon border p-2 bg-light d-inline-block">
                            <span class="material-symbols-rounded">
                                history_toggle_off
                            </span>
                        </span>
                    </div>
                    <time class="d-block text-center mini mb-0">
                        @if ($timeline->created_at->greaterThan(Carbon\Carbon::now()->subDay(1)))
                            {{ $timeline->created_at->diffForHumans() }}
                        @else
                            {{ Auth::user()->customer->formatDateTime($timeline->created_at, 'datetime_full') }}
                        @endif
                    </time>
                </div>
                <div class="w-100">
                    <action class="d-block mb-2">
                        @include('common.timelineMessage', [
                            'timeline' => $timeline,
                        ])
                    </action>
                    <div class="d-flex align-items-center">
                        {{-- <desc class="d-block small text-muted small text-end activity-desc">
                            <span class="material-symbols-rounded me-1">schedule</span> {{ Auth::user()->customer->formatDateTime($timeline->created_at, 'datetime_full') }}
                        </desc>
                        <span class="mx-2">·</span> --}}
                        <div class="d-block small text-muted2">
                            <div class="d-flex align-items-center">
                                <span class="material-symbols-rounded me-1">
                                    bookmark_border
                                </span>
                                <span>{{ trans('messages.timeline.type.' . $timeline->type) }}</span>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        @endif
    @endforeach
    <div class="mt-4">
        @include('elements/_per_page_select_short', ["items" => $timelines])
    </div>
    
@else
    <div class="empty-list">
        <i class="material-symbols-rounded">timeline</i>
        <span class="line-1">
            {{ trans('messages.automation.timeline.no_activities') }}
        </span>
    </div>
@endif