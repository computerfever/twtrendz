@extends('layouts.core.frontend_public', [
    'menu' => 'campaign',
])

@section('title', $campaign->name)

@section('page_header')
    
    @include("public.campaigns._header")

@endsection

@section('content')
                
    @include("public.campaigns._menu", [
        'menu' => 'links',
    ])

    <h3 class="mt-10"><span class="text-teal text-semibold">{{ count($campaign->getLinks()) }}</span> {{ trans('messages.links') }}</h3>
    
    <table class="table table-box pml-table table-head">
        <tr>
            <th>{{ trans('messages.url') }}</th>
            <th class="text-end">{{ trans('messages.total_clicks') }}</th>
            <th class="text-end">{{ trans('messages.last_clicked') }}</th>
        </tr>
        @foreach ($links as $link)
            <tr>
                <td>
                    <a class="url-truncate" title="{{ $link->url }}" href="{{ $link->url }}" target="_blank">
                        {{ $link->url }}
                    </a>
                </td>
                <td class="text-end">
                    {{ $link->clickCount }}
                </td>
                <td class="text-end">
                    {{ $link->lastClick }}
                </td>
            </tr>
        @endforeach
    </table>
    <br />
    <div class="text-end">
        <a href="{{ action('Pub\CampaignController@clickLog', ['uid' => $campaign->uid, 'customer_uid' => $campaign->customer->uid]) }}" class="btn btn-info bg-teal-600">{{ trans('messages.click_log') }} <span class="material-symbols-rounded">arrow_forward</span></a>
    </div>
@endsection
