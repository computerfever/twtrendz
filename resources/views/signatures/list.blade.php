@if ($signatures->count() > 0)
    <table class="table table-box pml-table mt-2"
        current-page="{{ empty(request()->page) ? 1 : empty(request()->page) }}"
    >
        @foreach ($signatures as $key => $signature)
            <tr class="position-relative">
                <td width="1%" class="list-check-col">
                    <div class="text-nowrap">
                        <div class="checkbox inline me-1">
                            <label>
                                <input type="checkbox" class="node styled"
                                    name="uids[]"
                                    value="{{ $signature->uid }}"
                                />
                            </label>
                        </div>
                    </div>
                </td>
                <td width="50%">
                    <h5 class="m-0 text-bold">
                        <a class="kq_search d-block" href="{{ action('SignatureController@edit', ["signature" => $signature->uid]) }}">{{ $signature->name }}</a>
                    </h5>
                    <span class="text-muted">{{ trans('messages.created_at') }}: {{ Auth::user()->customer->formatDateTime($signature->created_at, 'datetime_full') }}</span>
                </td>
                <td>
                    <span class="text-muted2 list-status pull-left">
                        <span class="label label-flat bg-{{ $signature->status }}">{{ trans('messages.signature.status.' . $signature->status) }}</span>
                    </span>
                </td>
                <td>
                    @if ($signature->is_default)
                        <span class="text-muted2 list-status pull-left">
                            <span class="label label-flat bg-active">{{ trans('messages.signature.default') }}</span>
                        </span>
                    @endif
                </td>
                <td class="text-end text-nowrap pe-0">
                    @if (Auth::user()->customer->can('update', $signature))
                        <a href="{{ action('SignatureController@edit', ["signature" => $signature->uid]) }}" title="{{ trans('messages.edit') }}" role="button" class="btn btn-secondary btn-icon">
                            <span class="material-symbols-rounded">edit</span>
                            {{ trans('messages.edit') }}
                        </a>
                    @endif
                    @if (Auth::user()->customer->can('delete', $signature) || Auth::user()->customer->can('disable', $signature) || Auth::user()->customer->can('enable', $signature))
                        <div class="btn-group">
                            <button role="button" class="btn btn-light dropdown-toggle" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @if (Auth::user()->customer->can('setDefault', $signature))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-method="POST" href="{{ action('SignatureController@setDefault', ["uid" => $signature->uid]) }}">
                                            <span class="material-symbols-rounded">check</span> {{ trans('messages.signature.set_default') }}
                                        </a>
                                    </li>
                                @endif
                                @if (Auth::user()->customer->can('enable', $signature))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-method="POST" link-confirm="{{ trans('messages.signature.enable.confirm') }}" href="{{ action('SignatureController@enable', ["uids" => $signature->uid]) }}">
                                            <span class="material-symbols-rounded">play_arrow</span> {{ trans('messages.enable') }}
                                        </a>
                                    </li>
                                @endif
                                @if (Auth::user()->customer->can('disable', $signature))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-method="POST" link-confirm="{{ trans('messages.signature.enable.confirm') }}" href="{{ action('SignatureController@disable', ["uids" => $signature->uid]) }}">
                                            <span class="material-symbols-rounded">hide_source</span> {{ trans('messages.disable') }}
                                        </a>
                                    </li>
                                @endif
                                @if (Auth::user()->customer->can('delete', $signature))
                                    <li>
                                        <a class="dropdown-item list-action-single" link-method="POST" link-confirm="{{ trans('messages.signature.delete.confirm') }}" href="{{ action('SignatureController@delete', ["uids" => $signature->uid]) }}">
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
    @include('elements/_per_page_select', [
        'items' => $signatures,
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
            {{ trans('messages.signature.empty') }}
        </span>
    </div>
@endif
