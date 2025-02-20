@extends('layouts.core.frontend', [
	'menu' => 'user',
])

@section('title', $user->displayName(get_localization_config('show_last_name_first', Auth::user()->customer->getLanguageCode())))
	
@section('page_header')
    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("AccountController@profile") }}">{{ Auth::user()->customer->displayName() }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("UserController@index") }}">{{ trans('messages.users') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">edit</span> {{ $user->displayName(get_localization_config('show_last_name_first', Auth::user()->customer->getLanguageCode())) }}</span>
        </h1>
    </div>
@endsection

@section('content')
    @include("account._menu", [
		'menu' => 'users',
	])

    <form enctype="multipart/form-data" action="{{ action('UserController@update', $user->uid) }}" method="POST">
        {{ csrf_field() }}

        @method('PATCH')
        
        @include('users._form')
    <form>
@endsection
