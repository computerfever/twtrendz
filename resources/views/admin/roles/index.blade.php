@extends('layouts.core.backend', [
	'menu' => 'role',
])

@section('title', trans('messages.campaigns'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        </ul>
        <h1>
            <span class="material-symbols-rounded">format_list_bulleted</span> {{ trans('messages.roles') }}</span>
        </h1>
    </div>

@endsection

@section('content')

    <div id="rolesIndexContainer" class="listing-form top-sticky"
        data-url="{{ action('Admin\RoleController@list') }}"
    >
        <div class="d-flex top-list-controls top-sticky-content">
            <div class="me-auto">
                <div class="filter-box">
                    <span class="filter-group">
                        <span class="title text-semibold text-muted">{{ trans('messages.sort_by') }}</span>
                        <select class="select" name="sort_order">
                            <option value="created_at">{{ trans('messages.created_at') }}</option>
                            <option value="email_address">{{ trans('messages.role.email_address') }}</option>
                        </select>
                        <input type="hidden" name="sort_direction" value="desc" />
                        <button type="button" class="btn btn-light sort-direction" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                            <span class="material-symbols-rounded desc">sort</span>
                        </button>
                    </span>
                    <span class="text-nowrap">
                        <input type="text" name="keyword" class="form-control search" value="{{ request()->keyword }}" value="{{ request()->keyword }}" placeholder="{{ trans('messages.type_to_search') }}" />
                        <span class="material-symbols-rounded">search</span>
                    </span>
                </div>
            </div>
            <div class="text-end">
                <a href="{{ action('Admin\RoleController@create') }}" role="button" class="btn btn-secondary">
                    <span class="material-symbols-rounded">add</span> {{ trans('messages.role.create') }}
                </a>
            </div>
        </div>

        <div id="rolesIndexContent" class="pml-table-container"></div>
    </div>

    <script>
        var rolesIndex = {
            list: null,
            getList: function() {
                if (this.list == null) {
                    this.list = makeList({
                        url: '{{ action('Admin\RoleController@list') }}',
                        container: $('#rolesIndexContainer'),
                        content: $('#rolesIndexContent')
                    });
                }
                return this.list;
            }
        };

        $(document).ready(function() {
            rolesIndex.getList().load();
        });
    </script>
@endsection
