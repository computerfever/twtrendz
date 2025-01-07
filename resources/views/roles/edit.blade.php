@extends('layouts.core.frontend_no_subscription', [
	'menu' => 'role',
])

@section('title', trans('messages.roles'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("RoleController@index") }}">{{ trans('messages.roles') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">edit</span> {{ $role->name }}</span>
        </h1>         
    </div>

@endsection

@section('content')
    @include("account._menu", [
		'menu' => 'role',
	])

    <form action="{{ action('RoleController@update', $role->uid) }}" method="POST" class="form-validate-jqueryz">
        @csrf
        
        @include('roles._form')
	</form>

@endsection
