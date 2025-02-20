@if ($servers->count() > 0)
    <div class="mt-3 plugins-list-container mb-4 pb-2"
        current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
    >
        @foreach ($servers as $key => $server)
            <div class="p-4 shadow-sm bg-white rounded-3 border">
                <div class="">
                    {{-- <div class="me-0">
                        <img class="plugin-icon me-4 rounded" src="{{ url('/images/plugin.svg') }}" />
                    </div> --}}
                    <div class="plugin-title-column plugin-title-{{ $server->name }}" >
                        <div class="d-flex align-items-center wp-100">
                            <h5 class="no-margin text-bold kq_search mb-0">
                                {{ $server->name }}                     
                            </h5>
                            <div class="ms-auto">
                                <span class="text-muted2 list-status pull-left small">
                                    <span class="label label-flat bg-{{ $server->status }}">
                                        {{ trans('messages.email_verification_server_status_' . $server->status) }}
                                    </span>
                                </span>
                            </div>
                        </div>
                            
                            
                        <span class="mt-1 d-block text-muted mb-1">
                            {{ $server->getTypeName() }}
                        </span>
                        <div class="">
                            <span class="text-muted2">{{ trans('messages.rate_limit.speed', ['limit' => $server->getSpeedLimitString()]) }}</span>
                        </div>
                        <span class="text-muted2 small mt-2 d-block">
                            {{ trans('messages.created_at') }}: {{ Auth::user()->admin->formatDateTime($server->created_at, 'datetime_full') }}
                        </span>

                        <div class="text-left text-nowrap pe-0 ms-auto mt-3">
                            
                            @if (Auth::user()->admin->can('update', $server))
                                <a href="{{ action('Admin\EmailVerificationServerController@edit', ["email_verification_server" => $server->uid]) }}" data-popup="tooltip" title="{{ trans('messages.edit') }}" role="button" class="btn btn-secondary btn-icon"><span class="material-symbols-rounded">edit</span> {{ trans('messages.edit') }}</a>
                            @endif
                            @if (Auth::user()->admin->can('delete', $server) || Auth::user()->admin->can('disable', $server) || Auth::user()->admin->can('enable', $server))
                                <div class="btn-group">
                                    <button role="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if (Auth::user()->admin->can('enable', $server))
                                            <li>
                                                <a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.enable_email_verification_servers_confirm') }}" href="{{ action('Admin\EmailVerificationServerController@enable', ["uids" => $server->uid]) }}">
                                                    <span class="material-symbols-rounded">play_arrow</span> {{ trans('messages.enable') }}
                                                </a>
                                            </li>
                                        @endif
                                        @if (Auth::user()->admin->can('disable', $server))
                                            <li>
                                                <a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.disable_email_verification_servers_confirm') }}" href="{{ action('Admin\EmailVerificationServerController@disable', ["uids" => $server->uid]) }}">
                                                    <span class="material-symbols-rounded">hide_source</span> {{ trans('messages.disable') }}
                                                </a>
                                            </li>
                                        @endif
                                        @if (Auth::user()->admin->can('delete', $server))
                                            <li>
                                                <a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.delete_email_verification_servers_confirm') }}" href="{{ action('Admin\EmailVerificationServerController@delete', ["uids" => $server->uid]) }}">
                                                    <span class="material-symbols-rounded">delete_outline</span> {{ trans('messages.delete') }}
                                                </a>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            @endif
                            {{-- @if (Auth::user()->admin->can('enable', $plugin))
                                <a link-confirm="{{ trans('messages.enable_plugins_confirm') }}"
                                    href="{{ action('Admin\PluginController@enable', ["uids" => $plugin->uid]) }}"
                                    class="btn btn-primary list-action-single"
                                >
                                    {{ trans('messages.enable') }}
                                </a>
                            @endif

                            @if (Auth::user()->admin->can('disable', $plugin))
                                <a link-confirm="{{ trans('messages.disable_plugins_confirm') }}"
                                    href="{{ action('Admin\PluginController@disable', ["uids" => $plugin->uid]) }}"
                                    class="btn btn-default list-action-single"
                                >
                                    {{ trans('messages.disable') }}
                                </a>
                            @endif

                            @if (isset($settingUrls[$plugin->name]))
                                <a
                                    href="{{ $settingUrls[$plugin->name] }}"
                                    class="btn btn-default"
                                >
                                    {{ trans('messages.setting') }}
                                </a>
                            @endif

                            <div class="btn-group">
                                <button role="button" class="btn btn-light icon-only dropdown-toggle" data-bs-toggle="dropdown">
                                    
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a
                                            class="dropdown-item"
                                            href="{{ action('Admin\PluginController@delete', ["uid" => $plugin->uid]) }}"
                                        >
                                            <i class="material-symbols-rounded">delete</i> {{ trans('messages.uninstall') }}
                                        </a>
                                    </li>
                                </ul>
                            </div> --}}
                        </div>
                    </div>
                </div>
                    
                
            </div>
        @endforeach
    </div>

    {{-- <table class="table table-box pml-table mt-2"
        current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
    >
        @foreach ($servers as $key => $server)
            <tr class="position-relative">
                <td width="1%" class="list-check-col">
                    <div class="text-nowrap">
                        <div class="checkbox inline me-1">
                            <label>
                                <input type="checkbox" class="node styled"
                                    name="uids[]"
                                    value="{{ $server->uid }}"
                                />
                            </label>
                        </div>
                    </div>
                </td>
                <td>
                    <h5 class="m-0 text-bold">
                        <a class="kq_search" href="{{ action('Admin\EmailVerificationServerController@edit', ['email_verification_server' => $server->uid ]) }}">{{ $server->name }}</a>
                    </h5>
                    <span class="text-muted">{{ trans('messages.created_at') }}: {{ Auth::user()->admin->formatDateTime($server->created_at, 'datetime_full') }}</span>
                </td>
                <td>
                    <div class="single-stat-box pull-left ml-4">
                        <span class="no-margin stat-num kq_search">{{ $server->getTypeName() }}</span>
                        <br />
                        <span class="text-muted">{{ trans('messages.email_verification_server_type') }}</span>
                    </div>
                </td>
                <td>
                    <div class="single-stat-box pull-left ml-4">
                        <span class="text-muted">###</span>
                        <br />
                        <span class="text-muted2">{{ trans('messages.sending_server.speed', ['limit' => $server->getSpeedLimitString()]) }}</span>
                    </div>
                </td>
                <td>
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-{{ $server->status }}">{{ trans('messages.email_verification_server_status_' . $server->status) }}</span>
                    </span>
                </td>
                <td class="text-end text-nowrap pe-0">
                    @if (Auth::user()->admin->can('update', $server))
                        <a href="{{ action('Admin\EmailVerificationServerController@edit', ["email_verification_server" => $server->uid]) }}" data-popup="tooltip" title="{{ trans('messages.edit') }}" role="button" class="btn btn-secondary btn-icon"><span class="material-symbols-rounded">edit</span> {{ trans('messages.edit') }}</a>
                    @endif
                    @if (Auth::user()->admin->can('delete', $server) || Auth::user()->admin->can('disable', $server) || Auth::user()->admin->can('enable', $server))
                        <div class="btn-group">
                            <button role="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if (Auth::user()->admin->can('enable', $server))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.enable_email_verification_servers_confirm') }}" href="{{ action('Admin\EmailVerificationServerController@enable', ["uids" => $server->uid]) }}">
                                            <span class="material-symbols-rounded">play_arrow</span> {{ trans('messages.enable') }}
                                        </a>
                                    </li>
                                @endif
                                @if (Auth::user()->admin->can('disable', $server))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.disable_email_verification_servers_confirm') }}" href="{{ action('Admin\EmailVerificationServerController@disable', ["uids" => $server->uid]) }}">
                                            <span class="material-symbols-rounded">hide_source</span> {{ trans('messages.disable') }}
                                        </a>
                                    </li>
                                @endif
                                @if (Auth::user()->admin->can('delete', $server))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-confirm="{{ trans('messages.delete_email_verification_servers_confirm') }}" href="{{ action('Admin\EmailVerificationServerController@delete', ["uids" => $server->uid]) }}">
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
    </table> --}}

    @include('elements/_per_page_select', [
        'items' => $servers,
    ])
    
@elseif (!empty(request()->keyword) || !empty(request()->filters["type"]))
    <div class="empty-list">
        <span class="material-symbols-rounded">dns</span>
        <span class="line-1">
            {{ trans('messages.no_search_result') }}
        </span>
    </div>
@else
    <div class="empty-list">
        <span class="material-symbols-rounded">dns</span>
        <span class="line-1">
            {{ trans('messages.email_verification_server_empty_line_1') }}
        </span>
    </div>
@endif
