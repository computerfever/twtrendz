@extends('layouts.core.backend', [
	'menu' => 'user',
])

@section('title', trans('messages.create_customer'))
	
@section('page_header')
    <div class="page-title">				
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("Admin\CustomerController@index") }}">{{ trans('messages.customers') }}</a></li>
            <li class="breadcrumb-item active">{{ trans('messages.user.add_new') }}</li>
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

    <form enctype="multipart/form-data" action="{{ action('Admin\UserController@store', [
        'customer_uid' => $customer->uid,
    ]) }}" method="POST">
        {{ csrf_field() }}
        
        @include('admin.users._form')
    <form>
@endsection
