@if ($roles->count() > 0)
	<table class="table table-box pml-table mt-2">
		@foreach ($roles as $key => $role)
            <tr>
                <td>
                    <a class="kq_search fs-6 d-block mb-1 fw-600" href="{{ action('Admin\RoleController@edit', $role->uid) }}">
                        {{ $role->name }}
                    </a>
                    <p class="mb-1">{{ $role->description }}</p>
                    <p class="mb-0">
                        <span class="text-muted">{{ trans('messages.created_at') }}:
                            {{ Auth::user()->admin->formatDateTime($role->created_at, 'datetime_full') }}
                        </span>
                    </p>
                </td>

                <td style="width:30%">
                    <div class="px-md-4">
                        <div class="mb-1">
                            <a class="" href="{{ action('Admin\RoleController@edit', $role->uid) }}">
                                <span class="badge bg-secondary">{{ $role->rolePermissions()->count() }}</span>
                            </a>
                        </div>
                        <span class="text-muted">{{ trans("messages.permissions") }}</span>
                    </div>
                </td>

                <td style="width:30%">
                    <div class="px-md-4">
                        <div class="mb-1">
                            <span class="" href="{{ action('Admin\RoleController@edit', $role->uid) }}">
                                <span class="badge bg-secondary">{{ $role->users()->count() }}</span>
                            </span>
                        </div>
                        <span class="text-muted">{{ trans("messages.role.user_count") }}</span>
                    </div>
                </td>

                <td width="20%">
                    <div class="single-stat-box pull-left">
                        <span class="no-margin stat-num">{{ $role->updated_at->diffForHumans() }}</span>
                        <br />
                        <span class="text-muted">{{ trans("messages.updated_at") }}</span>
                    </div>
                </td>

                <td width="10%">
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-{{ $role->status }}">{{ trans('messages.role.status.' . $role->status) }}</span>
                    </span>
                </td>

                <td class="text-end text-nowrap pe-0">
                    <div class="btn-group">
                        <a href="{{ action('Admin\RoleController@edit', $role->uid) }}" data-popup="tooltip"
                            title="{{ trans('messages.edit') }}" role="button"
                            class="btn btn-secondary btn-icon me-1"
                        >
                            <i class="icon-stats-growth"></i> {{ trans('messages.edit') }}
                        </a>
                        
                        @if (
                            Auth::user()->admin->can('enable', $role) ||
                            Auth::user()->admin->can('disable', $role) ||
                            Auth::user()->admin->can('delete', $role)
                        )
                            <button role="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if (Auth::user()->admin->can('enable', $role))
                                    <li><a
                                        class="dropdown-item list-action-single"
                                        link-method="POST"
                                        href="{{ action('Admin\RoleController@enable', [
                                            'uids' => [$role->uid],
                                        ]) }}">
                                        <span class="material-symbols-rounded me-2">task_alt</span> {{ trans("messages.enable") }}</a>
                                    </li>
                                @endif

                                @if (Auth::user()->admin->can('disable', $role))
                                    <li><a
                                        class="dropdown-item list-action-single"
                                        link-method="POST"
                                        href="{{ action('Admin\RoleController@disable', [
                                            'uids' => [$role->uid],
                                        ]) }}">
                                        <span class="material-symbols-rounded me-2">do_disturb_on</span> {{ trans("messages.disable") }}</a>
                                    </li>
                                @endif
                                
                                @if (Auth::user()->admin->can('delete', $role))
                                    <li><a
                                        class="dropdown-item list-action-single"
                                        link-method="POST"
                                        link-confirm="{{ trans('messages.role.delete.confirm') }}"
                                        href="{{ action('Admin\RoleController@delete', ["uids" => [$role->uid]]) }}">
                                        <span class="material-symbols-rounded me-2">delete_outline</span> {{ trans("messages.delete") }}</a></li>
                                @endif
                            </ul>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
	</table>
	@include('elements/_per_page_select', ["items" => $roles])

    <script>
        $(function() {
            
        });
    </script>
@elseif (!empty(request()->keyword) || !empty(request()->mail_list_uid))
	<div class="empty-list">
		<span class="material-symbols-rounded">auto_awesome</span>
		<span class="line-1">
			{{ trans('messages.no_search_result') }}
		</span>
	</div>
@else
	<div class="empty-list">
		<span class="material-symbols-rounded">auto_awesome</span>
		<span class="line-1">
			{{ trans('messages.role.empty_list') }}
		</span>
	</div>
@endif
