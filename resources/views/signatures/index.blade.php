@extends('layouts.core.frontend', [
	'menu' => 'signature',
])

@section('title', trans('messages.signature.signatures'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">format_list_bulleted</span> {{ trans('messages.signature.signatures') }}</span>
        </h1>
    </div>

@endsection

@section('content')
    <p>{{ trans('messages.signature.wording') }}</p>


    <div id="SignatureListContainer">
        <div class="d-flex top-list-controls top-sticky-content">
            <div class="me-auto">
                <div class="filter-box">
                    <div class="checkbox inline check_all_list">
                        <label>
                            <input type="checkbox" name="page_checked" class="styled check_all">
                        </label>
                    </div>
                    <div class="dropdown list_actions" style="display: none">
                        <button role="button" class="btn btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                            {{ trans('messages.actions') }} <span class="number"></span><span class="caret"></span>
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" link-method="POST" link-confirm="{{ trans('messages.signature.enable.confirm') }}" href="{{ action('SignatureController@enable') }}"><span class="material-symbols-rounded">play_arrow</span> {{ trans('messages.enable') }}</a></li>
                            <li><a class="dropdown-item" link-method="POST" link-confirm="{{ trans('messages.signature.disable.confirm') }}" href="{{ action('SignatureController@disable') }}"><span class="material-symbols-rounded">hide_source</span> {{ trans('messages.disable') }}</a></li>
                            <li><a class="dropdown-item" link-method="POST" link-confirm="{{ trans('messages.signature.delete.confirm') }}" href="{{ action('SignatureController@delete') }}"><span class="material-symbols-rounded">delete_outline</span> {{ trans('messages.delete') }}</a></li>
                        </ul>
                    </div>
                    <span class="filter-group">
                        <span class="title text-semibold text-muted">{{ trans('messages.sort_by') }}</span>
                        <select class="select" name="sort_order">
                            <option value="signatures.created_at">{{ trans('messages.created_at') }}</option>
                            <option value="signatures.name">{{ trans('messages.name') }}</option>
                            <option value="signatures.updated_at">{{ trans('messages.updated_at') }}</option>
                        </select>
                        <input type="hidden" name="sort_direction" value="desc" />
<button type="button" class="btn btn-light sort-direction" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                            <span class="material-symbols-rounded desc">sort</span>
                        </button>
                    </span>
                    <span class="text-nowrap">
                        <input type="text" name="keyword" class="form-control search" value="{{ request()->keyword }}" placeholder="{{ trans('messages.type_to_search') }}" />
                        <span class="material-symbols-rounded">search</span>
                    </span>
                </div>
            </div>
            @if (Auth::user()->customer->can('create', Acelle\Model\Signature::class))
                <div class="text-end">
                    <a href="{{ action('SignatureController@create') }}" role="button" class="btn btn-secondary">
                        <span class="material-symbols-rounded">add</span> {{ trans('messages.signature.add_new') }}
                    </a>
                </div>
            @endif
        </div>

        <div id="SignatureListContent">
        </div>
    </div>

    <script>
        var SignaturesIndex = {
            getList: function() {
                return makeList({
                    url: '{{ action('SignatureController@list') }}',
                    container: $('#SignatureListContainer'),
                    content: $('#SignatureListContent')
                });
            }
        };

        $(function() {
            SignaturesIndex.getList().load();
        });
    </script>
@endsection
