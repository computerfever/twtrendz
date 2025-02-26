@extends('core::layouts.frontend', ['menu' => 'landingpages'])
@section('title', __('Settings'))

@section('page_header')
  <div class="page-title">
    <ul class="breadcrumb breadcrumb-caret position-right">
      <li class="breadcrumb-item">
        <a href="{{ action("Admin\HomeController@index") }}">{{ trans('messages.home') }}</a>
      </li>
    </ul>
    <div class="d-sm-flex align-items-center justify-content-between mb-3">
    	<h1><span class="material-symbols-rounded">format_list_bulleted</span> {{$item->name}}</h1>
			<div class="my-3 my-lg-0 navbar-search">
				<div class="input-group">
					<div class="p-1">
						<a href="{{ route('landingpages.builder', $item->code) }}" class="btn btn-sm btn-primary">
							<i class="far fa-window-maximize"></i> Builder
						</a>
					</div>
				</div>
			</div>
		</div>
  </div>
@endsection

@section('content')
	<form role="form" method="post" action="{{ route('landingpages.settings.update',$item) }}" autocomplete="off" enctype="multipart/form-data">
		@csrf
		<div class="row">
			<div class="col-md-12 setting-tabs">
				<nav>
					<div class="nav nav-tabs" id="nav-tab" role="tablist">
						<a class="nav-item nav-link active" id="general" data-toggle="tab" href="#nav-general" role="tab" aria-controls="nav-general">@lang('General')</a>
						<a class="nav-item nav-link" id="domains" data-toggle="tab" href="#nav-domains" role="tab" aria-controls="nav-domains">@lang('Domain')</a>
						<a class="nav-item nav-link" id="nav-forms-tab" data-toggle="tab" href="#nav-forms" role="tab" aria-controls="nav-profile">@lang('Form')</a>
						<a class="nav-item nav-link" id="nav-fonts-tab" data-toggle="tab" href="#nav-fonts" role="tab" aria-controls="nav-fonts">@lang('Fonts')</a>
						<a class="nav-item nav-link" id="seo" data-toggle="tab" href="#nav-seo" role="tab" aria-controls="nav-seo">@lang('SEO')</a>
						<a class="nav-item nav-link d-none" id="nav-payment-tab" data-toggle="tab" href="#nav-payment" role="tab" aria-controls="nav-payment">
							@lang('Payment')
						</a>
						<a class="nav-item nav-link" id="custom-code" data-toggle="tab" href="#nav-custom-code" role="tab" aria-controls="nav-contact">@lang('Custom Code')</a>
					</div>
				</nav>
				<div class="tab-content" id="nav-tabContent">
					{{-- General --}}
					<div class="tab-pane fade show active" id="nav-general" role="tabpanel" aria-labelledby="nav-general">
						<h4 class="title-tab-content">@lang('General Settings')</h4>

						<div class="form-group">
							<label class="form-label">@lang('Name')</label>
							<input type="text" name="name" value="{{$item->name}}" class="form-control">
						</div>
						<div class="form-group mb-4 mt-4">
							<label class="custom-switch pl-0">
								<input type="checkbox" name="is_publish" value="1" class="custom-switch-input" {{ $item->is_publish ? 'checked' : '' }}>
								<span class="custom-switch-indicator"></span>
								<span class="custom-switch-description">@lang('Publish')</span>
							</label>
						</div>
						<div class="form-group">
							<label class="form-label">@lang('Favicon')</label>
							<input name="favicon" type="file" accept="image/*"><br>
							<small>@lang("Image will be displayed in browser tabs (best size 32 x 32)")</small>
							@if($item->favicon)
							<p><img src="{{ URL::to('/') }}/storage/pages/{{ $item->id }}/{{ $item->favicon }}" data-value="" class="img-thumbnail" /></p>
							@endif
						</div>
					</div>
					{{-- Domains --}}
					<div class="tab-pane fade" id="nav-domains" role="tabpanel" aria-labelledby="nav-domains-tab">
						<h4 class="title-tab-content">@lang('Domain Settings')</h4>

						<p class="title-break"><strong>@lang('Current domain'):</strong>
							@if($item->domain_type == 0)
							<a href="https://{{$item->sub_domain}}" target="_blank">{{$item->sub_domain}}</a>
							@elseif($item->domain_type == 1)
							<a href="https://{{$item->custom_domain}}">{{$item->custom_domain}}</a>
							@endif
						</p>
						<div class="form-group">
							<label class="form-label">@lang('Domain Type')</label>
							<select name="domain_type" id="domain_type_select" class="select">
								<option value="0" {{ !$item->domain_type ? 'selected' : '' }}>@lang('Sub domain')</option>
								<option value="1" {{ $item->domain_type ? 'selected' : '' }}>@lang('Custom your domain')</option>
							</select>
						</div>
						<input type="hidden" name="domain_type" value="0">

						<div class="row mb-0">
							<div class="col-md-4">
								<div class="form-group form_subdomain">
									<label class="form-label">@lang('Custom your domain')</label>
									<input type="text" name="custom_domain" value="{{$item->custom_domain}}" class="form-control input_custom_domain" {{ !$item->domain_type ? 'disabled' : '' }} placeholder="@lang('Enter your custom domain')">
								</div>
							</div>
							<input type="hidden" name="custom_domain" value="">

							{{-- <div class="col-md-5">
								<div class="form-group form_subdomain">
									<label class="form-label">@lang('Customdomain custom page url')</label>
									<input type="text" name="custom_url" value="{{$item->custom_url}}" class="form-control input_custom_domain" {{ !$item->domain_type ? 'disabled' : '' }} placeholder="@lang('Enter your Customdomain custom page url')">
								</div>
							</div> --}}
							{{-- <input type="hidden" name="custom_url" value=""> --}}

							<div class="col-md-6 mb-0">
								<div class="form-group form_customdomain">
									<label class="form-label">@lang('Sub domain')</label>
									<input type="text" name="sub_domain" value="{{$item->sub_domain}}" class="form-control" {{ $item->domain_type ? 'disabled' : '' }} id="input_sub_domain">
								</div>
							</div>
							

						</div>

						<div class="row">
							<div class="col-md-12">
								<p class="{{ $item->domain_type ? 'd-none' : '' }}" id="sub_domain_note">@lang('You can customize subdomain')</p>
								<div id="custom_domain_note" class="{{ !$item->domain_type ? 'd-none' : '' }}">
									<table class="table card-table table-vcenter text-nowrap">
										<p>@lang("Add records below in your domain provider's DNS settings")</p>
										<thead class="thead-dark">
											<tr>
												<th>@lang('TYPE')</th>
												<th>@lang('HOST')</th>
												<th>@lang('VALUE')</th>
												<th>@lang('TTL')</th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td>A</td>
												<td>@</td>
												<td>{{ config('app.SERVER_IP') }}</td>
												<td>Automatic</td>
											</tr>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
					{{-- Forms --}}
					<div class="tab-pane fade" id="nav-forms" role="tabpanel" aria-labelledby="nav-forms-tab">
						<ul class="nav nav-pills mb-3" id="" role="tablist">
							<li class="nav-item">
								<a class="nav-link active" id="next-action-tab" data-toggle="pill" href="#next-action-nav" role="tab" aria-controls="next-action-tab">@lang('Next Action')</a>
							</li>
							<li class="nav-item">
								<a class="nav-link" id="intergrations-tab" data-toggle="pill" href="#intergrations-nav" role="tab" aria-controls="intergrations-tab">@lang('Link List With Form')</a>
							</li>
						</ul>
						<div class="tab-content">
							<div class="tab-pane fade show active" id="next-action-nav" role="tabpanel" aria-labelledby="next-action-tab">
								<div class="row">
									<div class="col-md-12">
										<div class="d-flex">
											<h4 class="title-tab-content pb-0">@lang('Action after form submission')</h4>
										</div>
										<div class="form-group row">
											<div class="col-md-6">
												<label class="form-label">@lang('All the form in main pages when submit will...')</label>
												<select name="type_form_submit" id="type_form_submit" class="select">
													<option value="thank_you_page" {{ $item->type_form_submit == 'thank_you_page' ? 'selected' : '' }}>@lang('Go to default Thank You Page')</option>
													<option value="url" {{ $item->type_form_submit == 'url' ? 'selected' : '' }}>@lang('Redirect to any URL')</option>
												</select>
											</div>
											<div class="col-md-6">
												<div class="form-group row @if($item->type_form_submit == 'thank_you_page') d-none @endif" id="form_redirect_url">
													<div class="col-md-12">
														<label class="form-label">@lang('Redirect to:')</label>
														<input type="text" name="redirect_url" value="{{$item->redirect_url}}" class="form-control">
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							{{-- AutoResponder --}}
							<div class="tab-pane fade" id="autoresponder-nav" role="tabpanel" aria-labelledby="autoresponder-tab">
								<div class="row">
									<div class="col-md-12">
										<div class="">
											<p>
												@lang('All the form in main pages when submit will send a email...') <br/>
												<small class="text-danger">@lang('Make sure there is an <strong>email</strong> field in the form!')</small>
											</p>
										</div>
										<div class="form-group row">
											<div class="col-md-6">
												<div class="form-group row">
													<div class="col-md-12">
														<label class="form-label">@lang('Message title')</label>
														<input type="text" name="autoresponder[message_title]" placeholder="@lang('Message title')" value="{{$item_autoresponder->message_title}}" class="form-control">
													</div>
												</div>
												<div class="form-group row">
													<div class="col-md-12">
														<label class="form-label">@lang('Sender name')</label>
														<input type="text" name="autoresponder[sender_name]" placeholder="@lang('Sender name')" value="{{$item_autoresponder-> sender_name}}" class="form-control">
													</div>
												</div>
												<div class="form-group row">
													<div class="col-md-12">
														<label class="form-label">@lang('Sender email')</label>
														<input type="text" name="autoresponder[sender_email]" placeholder="@lang('sender@sender.com')" value="{{$item_autoresponder-> sender_email}}" class="form-control">
													</div>
												</div>
											</div>
											<div class="col-md-6">
												<div class="form-group row">
													<div class="col-md-12">
														<label class="form-label">@lang('Message text')</label>
														<textarea name="autoresponder[message_text]" id="autoresponder_message_text" rows="5" placeholder="@lang('Hi %name%, Thank your submit form. This is a free book. You can download it at here. https://book.link')" class="form-control">{{$item_autoresponder-> message_text}}</textarea>
														<p class="mt-2"><small>@lang('Type <strong>%field_name_attribute%</strong> so that the content entered by the lead into the form field will be pasted automatically').</small></p>
													</div>
													<p>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<div class="tab-pane fade" id="intergrations-nav" role="tabpanel" aria-labelledby="intergrations-tab">
								{{-- Intergration --}}
								<div class="row">
									<div class="col-md-12">
										<h4 class="d-none">@lang('Intergrations')</h4>
										<label class="form-label d-none">@lang('All the form in main pages when submit will...')</label>
										<div class="row intergration_row d-none">
											<div class="col-md-4">
												<div class="card text-center p-3" id="card_acellemail" data-type="acellemail" data-name="Acellemail">
													<div class="card-block">
														<img src="{{ asset('img/acellemail.png') }}">
													</div>
													<div class="mt-3 no-gutters">
														<h6 class="card-title">@lang('Acellemail')</h6>
													</div>
												</div>
											</div>
										</div>

										<input type="text" id="input_intergration_type" hidden="" name="intergration_type" value="acellemail" class="form-control">
										<div class="alert d-none" id="alert-intergration" role="alert">
										</div>
										<div class="d-none" id="spinner-loading">
											<div class="d-flex align-items-center" >
											  <strong>@lang('Loading')...</strong>
											  <div class="spinner-border ml-auto" role="status" aria-hidden="true"></div>
											</div>
										</div>
										@include('landingpage::intergrations.acellemail')

									</div>
								</div>
							</div>
						</div>
					</div>
					{{-- Fonts --}}
					<div class="tab-pane fade" id="nav-fonts" role="tabpanel" aria-labelledby="nav-fonts-tab">
						<h4 class="title-tab-content">@lang('Font Settings')</h4>
						<div class="row mb-2">
							<div class="col-md-12">
								<strong class="">@lang('Current Font'):</strong>
								<strong class="text-info" id="font_currently_label">{{ $item->settings->fontCurrently }}</strong>
							</div>
						</div>
						<div class="row mb-4">
							<div class="col-md-6">
								<input type="text" id="font_currently" value="{{ $item->settings->fontCurrently }}" name="fontCurrently" class="form-control" hidden>
								<input type="text" id="search_fonts" name="search_fonts" placeholder="Search Google fonts" class="form-control">
							</div>
						</div>
						<div class="row mb-4">
							<div class="col-md-10">
								<div class="d-none mb-2" id="spinner-loading-fonts">
									<div class="d-flex align-items-center" >
									 <strong>@lang('Loading')...</strong>
									 <div class="spinner-border ml-auto" role="status" aria-hidden="true"></div>
								   </div>
							   </div>
								<div id="list_fonts">
								</div>
							</div>
						</div>
					</div>
					{{-- SEO --}}
					<div class="tab-pane fade" id="nav-seo" role="tabpanel" aria-labelledby="nav-seo-tab">
						<h4 class="title-tab-content">@lang('SEO Settings')</h4>
						<p class="title-break">@lang('Specify here necessary information about your page. It will help search engines find your content').</p>
						<div class="form-group">
							<label class="form-label">@lang('SEO Title')</label>
							<input type="text" name="seo_title" value="{{$item->seo_title}}" class="form-control">
						</div>
						<div class="form-group">
							<label class="form-label">@lang('SEO Description')</label>
							<textarea name="seo_description" rows="3" class="form-control">{{$item->seo_description}}</textarea>
						</div>
						<div class="form-group">
							<label class="form-label">@lang('SEO Keywords')</label>
							<textarea name="seo_keywords" rows="3" class="form-control">{{$item->seo_keywords}}</textarea>
						</div>
						<p class="title-break">@lang('Customize how your page is viewed when it is shared on social networks').</p>
						<div class="form-group">
							<label class="form-label">@lang('Social Title')</label>
							<input type="text" name="social_title" value="{{$item->social_title}}" class="form-control">
						</div>
						<div class="form-group">
							<label class="form-label">@lang('Social Image')</label>
							<input name="social_image" type="file" accept="image/*"><br>
							<small>@lang("Upload an image that will be automatically displayed on your posts, on social media platforms like Facebook and Twitter... To display the photo seamlessly on all platforms, the ideal dimension is 1200x630, with a file size smaller than 300KB")</small>
							@if($item->social_image)
							<p><img src="{{ URL::to('/') }}/storage/pages/{{ $item->id }}/{{ $item->social_image }}" data-value="" class="img-thumbnail" /></p>
							@endif

						</div>
						<div class="form-group">
							<label class="form-label">@lang('Social Description')</label>
							<textarea name="social_description" rows="3" class="form-control">{{$item->social_description}}</textarea>
						</div>
					</div>
					{{-- Form payment --}}
					<div class="tab-pane fade" id="nav-payment" role="tabpanel" aria-labelledby="nav-payment-tab">
						<div class="row">
							<div class="col-md-12">
								<div class="d-flex">
									<h4 class="title-tab-content pb-0">@lang('Action after payment success')</h4>
								</div>
								<div class="form-group row">
									<div class="col-md-6">
										<label class="form-label">@lang('All the button payment in main pages when success will...')</label>
										<select name="type_payment_submit" id="type_payment_submit" class="select">
											<option value="thank_you_page" {{ $item->type_payment_submit == 'thank_you_page' ? 'selected' : '' }}>@lang('Go to default Thank You Page')</option>
											<option value="url" {{ $item->type_payment_submit == 'url' ? 'selected' : '' }}>@lang('Redirect to any URL')</option>
										</select>
									</div>
									<div class="col-md-6">
										<div class="form-group row @if($item->type_payment_submit == 'thank_you_page') d-none @endif" id="form_redirect_url_payment">
											<div class="col-md-12">
												<label class="form-label">@lang('Redirect to:')</label>
												<input type="text" name="redirect_url_payment" value="{{$item->redirect_url_payment}}" class="form-control">
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					{{-- Custom Code --}}
					<div class="tab-pane fade" id="nav-custom-code" role="tabpanel" aria-labelledby="nav-custom-code">
						<h4 class="title-tab-content">@lang('Insert Headers and Footers')</h4>
						<p>@lang('Insert Headers and Footers lets you insert code like Google Analytics, custom CSS, Facebook Pixel, Chat, and more to your LandingPage site header and footer')</p>

						<div class="form-group">
							<label class="form-label">@lang('Main Page Header')</label>
							<textarea name="custom_header" rows="3" class="form-control">{{$item->custom_header}}</textarea>
						</div>
						<div class="form-group">
							<label class="form-label">@lang('Main Page Footer')</label>
							<textarea name="custom_footer" rows="3" class="form-control">{{$item->custom_footer}}</textarea>
						</div>

						<div class="form-group">
							<label class="form-label">@lang('Thank You Page Header')</label>
							<textarea name="thank_custom_header" rows="3" class="form-control">{{$item->thank_custom_header}}</textarea>
						</div>
						<div class="form-group">
							<label class="form-label">@lang('Thank You Page Footer')</label>
							<textarea name="thank_custom_footer" rows="3" class="form-control">{{$item->thank_custom_footer}}</textarea>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="row">
			<div class="col-md-12">
				<div class="">
					<button class="btn btn-primary ml-auto">@lang('Save')</button>
				</div>
			</div>
		</div>
	</form>
	@push('scripts')
	<script type="text/javascript">
		var item_intergration     = @json($item_intergration);
		var url_load_list         = `{{ url('intergration/lists') }}`;
		var url_load_merge_fields = `{{ url('intergration/mergefields') }}`;
		var url_search_fonts      = `{{ url('getFonts') }}`;
		var _token                = `{{ csrf_token() }}`;
		var lang = {
			"selected_font" : "@lang('Selected font')",
			"select_a_font" : "@lang('Select a font')",
			"demo_font" : "@lang('Demo font')",
			"action" : "@lang('Action')",
			"font_name" : "@lang('Font name')",
		};
	</script>
	<script src="{{ Module::asset('landingpage:js/settings.js') }}"></script>
	@endpush
@endsection