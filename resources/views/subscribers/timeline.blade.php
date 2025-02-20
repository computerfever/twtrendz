@extends('layouts.core.frontend', [
	'menu' => 'subscriber',
])

@section('title', $list->name . ": " . trans('messages.create_subscriber'))

@section('head')
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/anytime.min.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/pickadate/picker.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/pickadate/picker.date.js') }}"></script>
@endsection

@section('page_header')

    @include("lists._header")

@endsection

@section('content')
    @include("lists._menu", [
		'menu' => 'subscriber',
	])
    
    @include("subscribers._header")

    <div class="row">
        <div class="col-sm-12 col-md-8">
            @include("subscribers._menu", [
                'menu' => 'timeline',
            ])

            @if(isSiteDemo())
                <div class="d-flex align-items-top mt-5">
                    <h3 class="mr-auto">{{ trans('messages.automation.contact.activity_feed') }}</h3>
                    <div class="">
                        <div class="mt-10">
                            <div class="dropdown">
                            <button class="btn btn-default bg-grey dropdown-toggle" role="button" id="dropdownMenu1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                {{ trans('messages.automation.contact.all_activities') }}
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenu1">
                                <li><a href="#">Open</a></li>
                                <li><a href="#">Click</a></li>
                                <li><a href="#">Subscribe</a></li>
                                <li><a href="#">Unsubscribe</a></li>
                                <li><a href="#">Updated</a></li>
                            </ul>
                            </div>
                        </div>
                    </div>
                </div>
                    
                <div class="activity-feed mt-3">
                    <label class="date small font-weight-semibold mb-0 divider">Timeline</label>
                    
                    <div class="activity d-flex py-3 px-4">
                        <div class="activity-media pr-4 text-center">
                            <time class="d-block text-center mini mb-2">3 days ago</time>
                            <i class="material-symbols-rounded bg-primary">forward_to_inbox</i>
                        </div>
                        <div class="small">
                            <action class="d-block font-weight-semibold mb-1">
                                User Ardella Goldrup receives email entitled "Follow up Email"
                            </action>
                            <desc class="d-block small text-muted">
                                <i class="material-symbols-rounded me-1">schedule</i> Jan 07th, 2020 09:36
                            </desc>
                        </div>
                    </div>
                                        <div class="activity d-flex py-3 px-4">
                        <div class="activity-media pr-4 text-center">
                            <time class="d-block text-center mini mb-2">3 days ago</time>
                            <i class="material-symbols-rounded bg-primary">forward_to_inbox</i>
                        </div>
                        <div class="small">
                            <action class="d-block font-weight-semibold mb-1">
                                User Ardella Goldrup receives email entitled "Welcome to our list"
                            </action>
                            <desc class="d-block small text-muted">
                                <i class="material-symbols-rounded me-1">schedule</i> Jan 07th, 2020 09:36
                            </desc>
                        </div>
                    </div>
                                        <div class="activity d-flex py-3 px-4">
                        <div class="activity-media pr-4 text-center">
                            <time class="d-block text-center mini mb-2">3 days ago</time>
                            <i class="lnr lnr-clock bg-secondary"></i>
                        </div>
                        <div class="small">
                            <action class="d-block font-weight-semibold mb-1">
                                Wait for 24 hours before proceeding with the next event for user Ardella Goldrup
                            </action>
                            <desc class="d-block small text-muted">
                                <i class="material-symbols-rounded me-1">schedule</i> Jan 07th, 2020 09:36
                            </desc>
                        </div>
                    </div>
                                        <div class="activity d-flex py-3 px-4">
                        <div class="activity-media pr-4 text-center">
                            <time class="d-block text-center mini mb-2">3 days ago</time>
                            <i class="material-symbols-rounded bg-warning">call_split</i>
                        </div>
                        <div class="small">
                            <action class="d-block font-weight-semibold mb-1">
                                User Ardella Goldrup reads email entitled "Welcome email"
                            </action>
                            <desc class="d-block small text-muted">
                                <i class="material-symbols-rounded me-1">schedule</i> Jan 07th, 2020 09:36
                            </desc>
                        </div>
                    </div>
                                        <div class="activity d-flex py-3 px-4">
                        <div class="activity-media pr-4 text-center">
                            <time class="d-block text-center mini mb-2">3 days ago</time>
                            <i class="material-symbols-rounded bg-success">merge</i>
                        </div>
                        <div class="small">
                            <action class="d-block font-weight-semibold mb-1">
                                User Ardella Goldrup subscribes to mail list, automation triggered!
                            </action>
                            <desc class="d-block small text-muted">
                                <i class="material-symbols-rounded me-1">schedule</i> Jan 07th, 2020 09:36
                            </desc>
                        </div>
                    </div>
                </div>
            @else
                <div class="activity-feed mt-3">
                    <div id="TimelineListContainer" class="listing-form top-sticky">
                        <div class="d-flex top-list-controls top-sticky-content mb-3">
                            <div>
                                <h3 class="mr-auto mb-0">{{ trans('messages.subscriber.activities') }}</h3>
                            </div>
                            <div class="ms-auto">
                                <div class="filter-box">
                                    <span class="filter-group me-0">
                                        <span class="title text-semibold text-muted">{{ trans('messages.timeline.type') }}</span>
                                        <select class="select" name="type">
                                            <option value="">{{ trans('messages.timeline.all_types') }}</option>
                                            @foreach (Acelle\Model\Timeline::allTypes() as $type)
                                                <option value="{{ $type }}">{{ trans('messages.timeline.type.' . $type) }}</option>
                                            @endforeach
                                            <option value="automation">{{ trans('messages.timeline.type.automation') }}</option>
                                        </select>
                                    </span>
                                    <span class="text-nowrap search-container hide">
                                        <input type="text" name="keyword" class="form-control search" value="{{ request()->keyword }}" value="{{ request()->keyword }}" placeholder="{{ trans('messages.type_to_search') }}" />
                                        <span class="material-symbols-rounded">search</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div id="TimelineListContent">



                        </div>
                    </div>

                    <script>
                        var TimelineList = {
                            getList: function() {
                                return makeList({
                                    url: '{{ action('SubscriberController@timelineList', [
                                        'id' => $subscriber->id,
                                    ]) }}',
                                    container: $('#TimelineListContainer'),
                                    content: $('#TimelineListContent')
                                });
                            }
                        };
                
                        $(document).ready(function() {
                            TimelineList.getList().load();
                        });
                    </script>
                </div>
            @endif
            
        </div>
    </div>
@endsection




