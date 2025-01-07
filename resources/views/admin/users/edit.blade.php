@extends('layouts.core.backend', [
	'menu' => 'user',
])

@section('title', $user->displayName(get_localization_config('show_last_name_first', Auth::user()->admin->getLanguageCode())))
	
@section('page_header')
    <div class="page-title">				
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("Admin\CustomerController@index") }}">{{ trans('messages.customers') }}</a></li>
            <li class="breadcrumb-item active">{{ $user->displayName(get_localization_config('show_last_name_first', Auth::user()->admin->getLanguageCode())) }}</li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">apartment</span> {{ $customer->displayName() }}</span>
        </h1>				
    </div>
@endsection

@section('content')
    @include('admin.customers._tabs', [
        'menu' => 'users',
    ])

    <form enctype="multipart/form-data" action="{{ action('Admin\UserController@update', [
        'customer_uid' => $customer->uid,
        'user' => $user->uid,
    ]) }}" method="POST">
        {{ csrf_field() }}

        @method('PATCH')
        
        @include('admin.users._form')
    <form>
@endsection
