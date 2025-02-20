@extends('layouts.core.backend', [
	'menu' => 'sending_credit_plan',
])

@section('title', trans('messages.sending_credit_plan.plan'))

@section('page_header')
    
	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
			<li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("Admin\SendingCreditPlanController@index") }}">{{ trans('messages.sending_credit_plan.plans') }}</a></li>
		</ul>
		<h1>
			<span class="text-semibold">
                <span class="material-symbols-rounded">badge</span>
                {{ $plan->name }}
            </span>
		</h1> 
	</div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-7">
            <form id="sendingserverCreate" action="{{ action('Admin\SendingCreditPlanController@update', $plan->uid) }}" method="POST">
                {{ csrf_field() }}
                <input type="hidden" name="_method" value="PATCH" />

                @include('admin.sending_credit_plans.form')

                <div>
                    <button type="submit" class="btn btn-primary me-1">
                        {{ trans('messages.save') }}
                    </button>
                    <a href="{{ action('Admin\SendingCreditPlanController@index') }}" class="btn btn-light">
                        {{ trans('messages.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
