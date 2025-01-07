@extends('layouts.core.frontend', [
    'menu' => 'site',
])

@section('title', trans('messages.dashboard'))

@section('page_header')
    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">web</span> {{ trans('messages.site.sites') }}</span>
        </h1>
    </div>
@endsection

@section('content')
    <p>{!! trans('messages.site.intro') !!}</p>

    @include('sites._menu')
@endsection
