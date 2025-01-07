@extends('layouts.core.backend', [
	'menu' => 'email_verification_plan',
])

@section('title', trans('messages.email_verification_plan.plan'))

@section('page_header')
    
	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
			<li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("Admin\EmailVerificationPlanController@index") }}">{{ trans('messages.email_verification_plan.plans') }}</a></li>
		</ul>
		<h1>
			<span class="text-semibold"><span class="material-symbols-rounded">badge</span>
                {{ trans('messages.email_verification_plan.add_new_plan') }}
            </span>
		</h1> 
	</div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-7">
            <form id="sendingserverCreate" action="{{ action('Admin\EmailVerificationPlanController@store') }}" method="POST">
                {{ csrf_field() }}

                @include('admin.email_verification_plans.form')

                <div>
                    <button type="submit" class="btn btn-primary me-1">
                        {{ trans('messages.save') }}
                    </button>
                    <a href="{{ action('Admin\EmailVerificationPlanController@index') }}" class="btn btn-light">
                        {{ trans('messages.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
