@extends('layouts.core.backend', [
	'menu' => 'customer',
])

@section('title', trans('messages.contact_information'))

@section('page_header')

	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
			<li class="breadcrumb-item"><a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("Admin\CustomerController@index") }}">{{ trans('messages.customers') }}</a></li>
			<li class="breadcrumb-item active">{{ trans('messages.contact_information') }}</li>
		</ul>
		<h1>
			<span class="text-semibold"><i class="icon-address-book3"></i> {{ $contact->company }} ({{ $contact->name($customer->getLanguageCode()) }})</span>
		</h1>
	</div>

@endsection

@section('content')

	@include('admin.customers._tabs')

	<form enctype="multipart/form-data" action="{{ action('Admin\CustomerController@contact', $customer->uid) }}" method="POST" class="form-validate-jqueryz">
		{{ csrf_field() }}

		<h2 class="text-semibold text-primary">{{ trans('messages.primary_account_contact') }}</h2>

		<div class="row">
            <div class="col-md-6">
                @if (get_localization_config('show_last_name_first', Auth::user()->admin->getLanguageCode()))
                    <div class="row">
                        <div class="col-md-6">
                            @include('helpers.form_control', ['type' => 'text', 'name' => 'last_name', 'value' => $contact->last_name, 'rules' => Acelle\Model\Contact::$rules])
                        </div>
                        <div class="col-md-6">
                            @include('helpers.form_control', ['type' => 'text', 'name' => 'first_name', 'value' => $contact->first_name, 'rules' => Acelle\Model\Contact::$rules])
                        </div>
                    </div>
                @else 
                    <div class="row">
                        <div class="col-md-6">
                            @include('helpers.form_control', ['type' => 'text', 'name' => 'first_name', 'value' => $contact->first_name, 'rules' => Acelle\Model\Contact::$rules])
                        </div>
                        <div class="col-md-6">
                            @include('helpers.form_control', ['type' => 'text', 'name' => 'last_name', 'value' => $contact->last_name, 'rules' => Acelle\Model\Contact::$rules])
                        </div>
                    </div>
                @endif
            </div>
                        <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'label' => trans('messages.company_organization'), 'name' => 'company', 'value' => $contact->company, 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'label' => trans('messages.office_phone'), 'name' => 'phone', 'value' => $contact->phone, 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'label' => trans('messages.email_at_work'), 'name' => 'email', 'value' => $contact->email, 'help_class' => 'customer_contact', 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'name' => 'address_1', 'value' => $contact->address_1, 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'name' => 'city', 'value' => $contact->city, 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'label' => trans('messages.state_province_region'), 'name' => 'state', 'value' => $contact->state, 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'label' => trans('messages.zip_postal_code'), 'name' => 'zip', 'value' => $contact->zip, 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'name' => 'address_2', 'value' => $contact->address_2, 'rules' => Acelle\Model\Contact::$rules])
            </div>

            @if (config('custom.japan'))
                <input type="hidden" name="country_id" value="{{ Acelle\Model\Country::getJapan()->id }}" />
            @else
                <div class="col-md-6">
                    @include('helpers.form_control', ['type' => 'select', 'name' => 'country_id', 'label' => trans('messages.country'), 'value' => $contact->country_id, 'options' => Acelle\Model\Country::getSelectOptions(), 'include_blank' => trans('messages.choose'), 'rules' => Acelle\Model\Contact::$rules])
                </div>
            @endif
            

            <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'label' => trans('messages.website_url'), 'placeholder' => 'https://my.tupperware.com/','name' => 'url', 'value' => $contact->url, 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-6">
                @include('helpers.form_control', ['type' => 'text', 'label' => trans('messages.consultant_id'), 'name' => 'consultant_id', 'value' => $contact->consultant_id, 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-12">
                @include('helpers.form_control', ['type' => 'textarea', 'label' => trans('messages.consultant_message'), 'name' => 'message', 'value' => $contact->message, 'rows'=>5, 'rules' => Acelle\Model\Contact::$rules])
            </div>
            <div class="col-md-12">
                @include('helpers.form_control', ['type' => 'textarea', 'label' => trans('messages.page_message'), 'name' => 'page_message', 'value' => $contact->page_message, 'rows'=>5, 'rules' => Acelle\Model\Contact::$rules])
            </div>
        </div>

		<h2 class="text-semibold text-primary mt-4">{{ trans('messages.billing_information') }}</h2>
		<div class="row">
			<div class="col-md-6">

				@include('helpers.form_control', [
					'type' => 'text',
					'name' => 'tax_number',
					'value' => $contact->tax_number,
                    'label' => trans('messages.contact.tax_number'),
					'help_class' => 'customer_contact',
					'rules' => Acelle\Model\Contact::$rules]
				)

			</div>
			<div class="col-md-6">

				@include('helpers.form_control', [
					'type' => 'text',
					'name' => 'billing_address',
					'value' => $contact->billing_address,
					'help_class' => 'customer_contact',
					'rules' => Acelle\Model\Contact::$rules]
				)

			</div>
		</div>

		<hr>
		<div class="">
			<button class="btn btn-secondary"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
		</div>

	<form>

@endsection
