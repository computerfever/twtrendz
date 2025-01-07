@extends('layouts.core.backend', [
	'menu' => 'customer',
])

@section('title', trans('messages.create_customer'))
	
@section('page_header')
	
    <div class="page-title">
        <ul class="breadcrumb breadcrumb-caret position-right">
            <li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("Admin\CustomerController@index") }}">{{ trans('messages.customers') }}</a></li>
        </ul>
        <h1>
            <span class="text-semibold"><span class="material-symbols-rounded">add</span> {{ trans('messages.create_customer') }}</span>
        </h1>
    </div>

@endsection

@section('content')
    <form enctype="multipart/form-data" action="{{ action('Admin\CustomerController@store') }}" method="POST" class="form-validate-jqueryz">
        {{ csrf_field() }}
		
        <div class="row">
            <div class="col-md-6">
                @include('admin.customers._form')
                <hr>
                <div class="sub_section mb-0">
                    <h3 class="text-semibold text-primary mb-4">{{ trans('messages.account.default_user') }}</h3>
        
                    <div class="row">
                        <div class="col-md-12">
                            <div class="sub_section">
                                @if (get_localization_config('show_last_name_first', Auth::user()->admin->getLanguageCode()))
                                    <div class="row">
                                        <div class="col-md-6">
                                            @include('helpers.form_control', [
                                                'type' => 'text',
                                                'name' => 'last_name',
                                                'value' => $user->last_name,
                                                'rules' => $user->rules()
                                            ])
                                        </div>
                                        <div class="col-md-6">
                                            @include('helpers.form_control', [
                                                'type' => 'text',
                                                'name' => 'first_name',
                                                'value' => $user->first_name,
                                                'rules' => $user->rules()
                                            ])
                                        </div>
                                    </div>
                                @else 
                                    <div class="row">
                                        <div class="col-md-6">
                                            @include('helpers.form_control', [
                                                'type' => 'text',
                                                'name' => 'first_name',
                                                'value' => $user->first_name,
                                                'rules' => $user->rules()
                                            ])
                                        </div>
                                        <div class="col-md-6">
                                            @include('helpers.form_control', [
                                                'type' => 'text',
                                                'name' => 'last_name',
                                                'value' => $user->last_name,
                                                'rules' => $user->rules()
                                            ])
                                        </div>
                                    </div>
                                @endif
                    
                                @include('helpers.form_control', [
                                    'type' => 'text',
                                    'name' => 'email',
                                    'value' => $user->email,
                                    'help_class' => 'profile',
                                    'rules' => $user->rules()
                                ])
                    
                                @include('helpers.form_control', [
                                    'type' => 'password',
                                    'label'=> trans('messages.new_password'),
                                    'name' => 'password',
                                    'rules' => $user->rules()
                                ])
                    
                                @include('helpers.form_control', [
                                    'type' => 'password',
                                    'name' => 'password_confirmation',
                                    'rules' => $user->rules()
                                ])
                    
                                <div class="mb-3">
                                    <label for="" class="form-label">{{ trans('messages.user.role') }}</label>
                                    <select name="role_uid" class="form-select {{ $errors->has('role_uid') ? 'is-invalid' : '' }}">
                                        <option value="">{{ trans('messages.user.select_role') }}</option>
                                        @foreach (Acelle\Model\Role::active()->get() as $role)
                                            <option {{ $user->getRole() && $role->id == $user->getRole()->id || request()->role_uid == $role->uid  ? 'selected' : '' }} value="{{ $role->uid }}">{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    
                    </div>
        
                </div>
            </div>
        </div>		
        
        <hr>
        <div class="text-left">
            <button class="btn btn-secondary"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
        </div>
					
    <form>	
@endsection
