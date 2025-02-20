@if ($users->count() > 0)
    <table class="table table-box pml-table mt-2"
        current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
    >
        @foreach ($users as $key => $user)
            <tr>
                <td width="1%">
                    <img width="50" class="rounded-circle me-2" src="{{ $user->getProfileImageUrl() }}" alt="">
                </td>
                <td>
                    <h5 class="m-0 text-bold">
                        <a class="kq_search d-block" href="{{ action('Admin\UserController@edit', [
                            'customer_uid' => $customer->uid,
                            "user" => $user->uid,
                        ]) }}">{{ $user->displayName(get_localization_config('show_last_name_first', Auth::user()->admin->getLanguageCode())) }}</a>
                    </h5>
                    <span class="text-muted kq_search">{{ $user->email }}</span><br>
                </td>
                <td>
					<h5 class="m-0 fw-semibold">
                        <span class="no-margin kq_search">{{ Auth::user()->admin->formatDateTime($user->created_at, 'datetime_full') }}</span>
                    </h5>
					<span class="text-muted">{{ trans('messages.created_at') }}</span>
				</td>
                <td>
					<h5 class="m-0 fw-semibold">
                        {{ $user->getRole() ? $user->getRole()->name : '--' }}
                    </h5>
					<span class="text-muted">{{ trans('messages.user.role') }}</span>
				</td>
                <td>
                    @if ($user->isActivated())
                        <span class="text-muted2 list-status pull-left">
                            <span class="label label-flat bg-activated">{{ trans('messages.user.status.activated') }}</span>
                        </span>
                    @else
                        <span class="text-muted2 list-status pull-left">
                            <span class="label label-flat bg-deactivated">{{ trans('messages.user.status.deactivated') }}</span>
                        </span>
                    @endif
                </td>
                <td class="text-end">
                    @can('loginAs', $user)
                        <a href="{{ action('Admin\UserController@loginAs', [
                            'customer_uid' => $customer->uid,
                            'uid' => $user->uid,
                        ]) }}" data-popup="tooltip"
                            title="{{ trans('messages.login_as_this_user') }}" role="button"
                            class="btn btn-primary btn-icon"><span class="material-symbols-rounded">login</span></a>
                    @endcan
                    @if(Auth::user()->admin->can('update', $user))
                        <a href="{{ action('Admin\UserController@edit', [
                            'customer_uid' => $customer->uid,
                            'user' => $user->uid,
                        ]) }}"
                            data-popup="tooltip" title="{{ trans('messages.edit') }}"
                            role="button" class="btn btn-secondary btn-icon"><span class="material-symbols-rounded">edit</span></a>
                    @endif
                    @if (
                        Auth::user()->admin->can('enable', $user) ||
                        Auth::user()->admin->can('disable', $user) ||
                        Auth::user()->admin->can('delete', $user)
                    )
                        <div class="btn-group">
                            <button role="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a list-action="one-click-login" class="dropdown-item" href="{{ action('Admin\UserController@oneClickLogin', $user->uid) }}">
                                        <span class="material-symbols-rounded">link</span> {{ trans('messages.admin.generate_one_click_login') }}
                                    </a>
                                </li>
                                @if(Auth::user()->admin->can('enable', $user))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.user.enable.confirm') }}" href="{{ action('Admin\UserController@enable', [
                                            'customer_uid' => $customer->uid,
                                            "uids" => $user->uid,
                                        ]) }}">
                                            <span class="material-symbols-rounded">play_arrow</span> {{ trans('messages.enable') }}
                                        </a>
                                    </li>
                                @endif
                                @if(Auth::user()->admin->can('disable', $user))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.user.disable.confirm') }}" href="{{ action('Admin\UserController@disable', [
                                            'customer_uid' => $customer->uid,
                                            "uids" => $user->uid,
                                        ]) }}">
                                            <span class="material-symbols-rounded">hide_source</span> {{ trans('messages.disable') }}
                                        </a>
                                    </li>
                                @endif
                                @if(Auth::user()->admin->can('delete', $user))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.user.delete.confirm') }}" href="{{ action('Admin\UserController@delete', [
                                            'customer_uid' => $customer->uid,
                                            "uids" => $user->uid,
                                        ]) }}">
                                            <span class="material-symbols-rounded">delete_outline</span> {{ trans('messages.delete') }}
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif
                </td>
            </tr>
        @endforeach
    </table>
    @include('elements/_per_page_select', ["items" => $users])

    <script>
        var AdminUsersList = {
            oneClickPopup: null,

            init: function() {
                this.oneClickPopup = new Popup();
            }
        }

        $(function() {
            AdminUsersList.init();

            $('[list-action="one-click-login"]').on('click', function(e) {
                e.preventDefault();
                var url = $(this).attr('href');

                AdminUsersList.oneClickPopup.load(url);
            })
        })
    </script>

@elseif (!empty(request()->keyword))
    <div class="empty-list">
        <span class="material-symbols-rounded">people_outline</span>
        <span class="line-1">
            {{ trans('messages.no_search_result') }}
        </span>
    </div>
@else
    <div class="empty-list">
        <span class="material-symbols-rounded">people_outline</span>
        <span class="line-1">
            {{ trans('messages.customer_empty_line_1') }}
        </span>
    </div>
@endif
