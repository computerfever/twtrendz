@extends('layouts.core.frontend', [
	'menu' => 'user',
])

@section('title', trans('messages.users'))

@section('page_header')
    <div class="page-title">				
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item active">{{ Auth::user()->customer->displayName() }}</li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">group</span> {{ trans('messages.users') }}</span>
        </h1>				
    </div>
@endsection

@section('content')				
    @include("account._menu", [
		'menu' => 'users',
	])

	<div class="UserListContainer"
		per-page="15"					
	>				
		<div class="d-flex top-list-controls top-sticky-content">
			<div class="me-auto">
				<div class="filter-box">
                    <span class="filter-group">
                        <span class="title text-semibold text-muted">{{ trans('messages.sort_by') }}</span>
                        <select class="select" name="sort_order">											
                            <option value="users.created_at">{{ trans('messages.created_at') }}</option>
                            <option value="users.updated_at">{{ trans('messages.updated_at') }}</option>
                        </select>										
                        <input type="hidden" name="sort_direction" value="desc" />
<button type="button" class="btn btn-xs sort-direction" data-popup="tooltip" title="{{ trans('messages.change_sort_direction') }}" role="button" class="btn btn-xs">
                            <span class="material-symbols-rounded desc">sort</span>
                        </button>
                    </span>									
                    <span class="text-nowrap">
                        <input type="text" name="keyword" class="form-control search" value="{{ request()->keyword }}" placeholder="{{ trans('messages.type_to_search') }}" />
                        <span class="material-symbols-rounded">search</span>
                    </span>
                </div>
			</div>
			@if(Auth::user()->customer->can('create', new Acelle\Model\User()))
				<div class="text-end">
					<a href="{{ action("UserController@create") }}" role="button" class="btn btn-secondary">
						<span class="material-symbols-rounded">add</span> {{ trans('messages.user.add_new') }}
					</a>
				</div>
			@endif
		</div>
		
		<div id="UserListContent">
		</div>
	</div>

	<script>
		var UsersIndex = {
			getList: function() {
				return makeList({
					url: '{{ action('UserController@listing') }}',
					container: $('#UserListContainer'),
					content: $('#UserListContent')
				});
			}
		};

		$(document).ready(function() {
			UsersIndex.getList().load();
		});
	</script>

@endsection
