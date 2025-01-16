@extends('layouts.core.frontend', [
    'menu' => 'campaign',
])

@section('title', trans('messages.select_campaign_type'))

@section('page_header')
<div class="page-title">
    <ul class="breadcrumb breadcrumb-caret position-right">
        <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        <li class="breadcrumb-item"><a href="{{ action("CampaignController@index") }}">{{ trans('messages.campaigns') }}</a></li>
    </ul>
    <h1>
        <span class="text-semibold"><span class="material-symbols-rounded">schedule</span> {{ trans('messages.select_campaign_type') }}</span>
    </h1>
</div>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <ul class="modern-listing big-icon no-top-border-list mt-0">

            @can("admin_access", Auth::user())
                <h3>For Lists Subscribers</h3>
                <hr>
            @endif

            @foreach (Acelle\Model\Campaign::types() as $key => $type)

                <li>
                    <a href="{{ action("CampaignController@create", ["type" => $key]) }}" class="btn btn-secondary">{{ trans('messages.choose') }}</a>
                    <div class="d-flex pe-4">
                        <a href="{{ action("CampaignController@create", ["type" => $key]) }}">
                            <span class="pt-1 d-block">
                                @if ($key == 'regular')
                                    <img width="40px" class="icon-img d-inline-block me-4" src="{{ url('images/icons/regular.svg') }}" />
                                @elseif ($key == 'plain-text')
                                    <img width="40px" class="icon-img d-inline-block me-4" src="{{ url('images/icons/plain.svg') }}" />
                                @endif
                            </span>
                        </a>
                        <div>
                            <h4 class="mb-1"><a href="{{ action("CampaignController@create", ["type" => $key]) }}">{{ trans('messages.' . $key) }}</a></h4>
                            <p>
                                {{ trans('messages.campaign_intro_' . $key) }}
                            </p>
                        </div>
                    </div>
                        
                </li>

            @endforeach

            @can("admin_access", Auth::user())

                <h3 class="mb-0 mt-5">For Customers Newsletter List Subcribers</h3>

                <hr>

                @foreach (Acelle\Model\Campaign::types() as $key => $type)

                    <li>
                        <a href="{{ action("CampaignController@create", ["type" => $key,"admin"=>1]) }}" class="btn btn-secondary">{{ trans('messages.choose') }}</a>
                        <div class="d-flex pe-4">
                            <a href="{{ action("CampaignController@create", ["type" => $key,"admin"=>1]) }}">
                                <span class="pt-1 d-block">
                                    @if ($key == 'regular')
                                        <img width="40px" class="icon-img d-inline-block me-4" src="{{ url('images/icons/regular.svg') }}" />
                                    @elseif ($key == 'plain-text')
                                        <img width="40px" class="icon-img d-inline-block me-4" src="{{ url('images/icons/plain.svg') }}" />
                                    @endif
                                </span>
                            </a>
                            <div>
                                <h4 class="mb-1">
                                    <a href="{{ action("CampaignController@create", ["type" => $key,"admin"=>1]) }}">
                                        {{ trans('messages.' . $key) }}
                                    </a>
                                </h4>
                                <p>
                                    {{ trans('messages.campaign_intro_' . $key) }}
                                </p>
                            </div>
                        </div>
                            
                    </li>

                @endforeach

                <br><br><br>

            @endif

        </ul>
        <div class="">
            <a href="{{ action('CampaignController@index') }}" role="button" class="btn btn-secondary">
                <i class="icon-cross2"></i> {{ trans('messages.cancel') }}
            </a>
        </div>
    </div>
    <div class="col-md-1"></div>
</div>
@endsection