@extends('layouts.core.backend', [
	'menu' => 'role',
])

@section('title', trans('messages.roles'))

@section('page_header')

    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("Admin\RoleController@index") }}">{{ trans('messages.roles') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">edit</span> {{ $role->name }}</span>
        </h1>         
    </div>

@endsection

@section('content')
    <form action="{{ action('Admin\RoleController@update', $role->uid) }}" method="POST" class="form-validate-jqueryz">
        @csrf
        
        @include('admin.roles._form')
	</form>

@endsection
