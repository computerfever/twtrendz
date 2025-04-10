@extends('layouts.core.frontend', [
    'menu' => 'campaign',
])

@section('title', trans('messages.campaigns'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        </ul>
        <h1>
            <span class="material-symbols-rounded">format_list_bulleted</span> {{ trans('messages.campaigns') }}</span>
        </h1>
    </div>

@endsection

@section('content')
    <div id="CampaignsIndexContainer" class="listing-form top-sticky"
        data-url="{{ action('CampaignController@listing') }}"
        per-page="{{ Acelle\Model\MailList::$itemsPerPage }}"
    >
        <div class="d-flex top-list-controls top-sticky-content">
            <div class="me-auto">
                @if ($campaigns->count() >= 0)
                    <div class="filter-box">
                        <div class="checkbox inline check_all_list">
                            <label>
                                <input type="checkbox" name="page_checked" class="styled check_all">
                            </label>
                        </div>
                        <div class="dropdown list_actions" style="display: none">
                            <button type="button"
                                id="dropdownListActions"
                                class="btn btn-secondary dropdown-toggle"
                                data-bs-toggle="dropdown"
                            >
                                {{ trans('messages.actions') }} <span class="number"></span><span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownListActions">
                                <li>
                                    <a class="action dropdown-item"
                                        link-method="POST"
                                        link-confirm="{{ trans('messages.restart_campaigns_confirm') }}" href="{{ action('CampaignController@resume') }}">
                                        <span class="material-symbols-rounded me-2">restore</span> {{ trans("messages.resume") }}</a></li>
                                <li><a class="action dropdown-item"
                                    link-method="POST"
                                    link-confirm="{{ trans('messages.pause_campaigns_confirm') }}" href="{{ action('CampaignController@pause') }}">
                                    <span class="material-symbols-rounded me-2">motion_photos_pause</span> {{ trans("messages.pause") }}</a></li>
                                <li><a class="action dropdown-item"
                                    link-method="POST"
                                    link-confirm="{{ trans('messages.delete_campaigns_confirm') }}" href="{{ action('CampaignController@delete') }}">
                                    <span class="material-symbols-rounded me-2">delete_outline</span> {{ trans('messages.delete') }}</a></li>
                            </ul>
                        </div>
                        <span class="filter-group">
                            <span class="title text-semibold text-muted">{{ trans('messages.sort_by') }}</span>
                            <select class="select" name="sort_order">
                                <option value="created_at">{{ trans('messages.created_at') }}</option>
                                <option value="name">{{ trans('messages.name') }}</option>
                            </select>
                            <input type="hidden" name="sort_direction" value="desc" />
<button type="button" class="btn btn-light sort-direction" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                                <span class="material-symbols-rounded desc">sort</span>
                            </button>
                        </span>
                        <span class="filter-group">
                            <span class="title text-semibold text-muted">{{ trans('messages.campaign.status') }}</span>
                            <select class="select" name="status">
                                <option value="">{{ trans('messages.campaign.status.all') }}</option>
                                @foreach (Acelle\Model\Campaign::statusSelectOptions() as $option)
                                    <option {{ request()->status == $option['value'] ? 'selected' : '' }} value="{{ $option['value'] }}">{{ $option['text'] }}</option>
                                @endforeach
                            </select>
                        </span>
                        <span class="text-nowrap search-container">
                            <input type="text" name="keyword" class="form-control search" value="{{ request()->keyword }}" value="{{ request()->keyword }}" placeholder="{{ trans('messages.type_to_search') }}" />
                            <span class="material-symbols-rounded">search</span>
                        </span>
                    </div>
                @endif
            </div>
            <div class="text-end">
                @if (Auth::user()->customer->can('create', new Acelle\Model\Campaign()))
                    <a href="{{ action('CampaignController@selectType') }}" role="button" class="btn btn-secondary">
                        <span class="material-symbols-rounded">add</span> {{ trans('messages.create_campaign') }}
                    </a>
                @endif
            </div>
        </div>

        <div id="CampaignsIndexContent" class="pml-table-container">



        </div>
    </div>

    <script>
        var CampaignsIndex = {
            getList: function() {
                return makeList({
                    url: '{{ action('CampaignController@listing') }}',
                    container: $('#CampaignsIndexContainer'),
                    content: $('#CampaignsIndexContent')
                });
            }
        };

        $(document).ready(function() {
            CampaignsIndex.getList().load();
        });
    </script>
@endsection
