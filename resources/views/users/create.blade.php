@extends('layouts.core.frontend', [
	'menu' => 'user',
])

@section('title', trans('messages.create_customer'))
	
@section('page_header')
    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("AccountController@profile") }}">{{ Auth::user()->customer->displayName() }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("UserController@index") }}">{{ trans('messages.users') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">add</span> {{ trans('messages.user.add_new') }}</span>
        </h1>
    </div>
@endsection

@section('content')
    @include("account._menu", [
		'menu' => 'users',
	])

    <form enctype="multipart/form-data" action="{{ action('UserController@store') }}" method="POST">
        {{ csrf_field() }}
        
        @include('users._form')
    <form>
@endsection
