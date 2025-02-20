@extends('layouts.core.backend', [
	'menu' => 'sending_credit_plan',
])

@section('title', trans('messages.sending_credit_plan.plans'))

@section('head')
    <script type="text/javascript" src="{{ AppUrl::asset('core/js/group-manager.js') }}"></script>
@endsection

@section('page_header')

    <div class="page-title" style="padding-bottom:0">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold">{{ trans('messages.sending_credit_plan.plans') }}</span>
        </h1>
    </div>

@endsection

@section('content')

    <div class="row">
        <div class="col-md-8"><p>{{ trans('messages.sending_credit_plan.intro') }}</p></div>
    </div>

    <div class="listing-form"
        sort-url="{{ action('Admin\PlanController@sort') }}"
        data-url="{{ action('Admin\PlanController@listing') }}"
        per-page="{{ Acelle\Model\PlanGeneral::$itemsPerPage }}"
    >
        <div class="d-flex top-list-controls top-sticky-content">
            <div class="me-auto">
                <div class="filter-box">
                    <span class="filter-group">
                        <span class="title text-semibold text-muted">{{ trans('messages.sort_by') }}</span>
                        <select class="select" name="sort_order">
                            <option value="sending_credit_plans.name">{{ trans('messages.name') }}</option>
                            <option value="sending_credit_plans.created_at">{{ trans('messages.created_at') }}</option>
                        </select>
                        <input type="hidden" name="sort_direction" value="asc" />
                                            <button class="btn btn-xs sort-direction" rel="asc" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                            <span class="material-symbols-rounded desc">sort</span>
                        </button>
                    </span>
                    <span class="text-nowrap">
                        <input type="text" name="keyword" class="form-control search" value="{{ request()->keyword }}" placeholder="{{ trans('messages.type_to_search') }}" />
                        <span class="material-symbols-rounded">search</span>
                    </span>
                </div>
            </div>
            @can('create', new Acelle\Model\PlanGeneral())
                <div class="text-end">
                    <a href="{{ action('Admin\SendingCreditPlanController@create') }}" role="button" class="btn btn-secondary">
                        <span class="material-symbols-rounded">add</span> {{ trans('messages.sending_credit_plan.add_new_plan') }}
                    </a>
                </div>
            @endcan
        </div>

        <div class="pml-table-container">
        </div>
    </div>

    <script>
        var PlanIndex = {
            getList: function() {
                return makeList({
                    url: '{{ action('Admin\SendingCreditPlanController@list') }}',
                    container: $('.listing-form'),
                    content: $('.pml-table-container')
                });
            }
        };

        $(function() {
            PlanIndex.getList().load();
        });
    </script>
@endsection