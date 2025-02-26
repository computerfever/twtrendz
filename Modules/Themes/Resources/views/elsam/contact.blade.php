@extends('themes::elsam.layout')
@section('content')
    @include('themes::elsam.nav')
    <section class="pt-4 pb-4">
        <div class="home-center">
            <div class="home-desc-center">
                <div class="container">
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="row mb-2 text-center">
                                <div class="col-md-12">
                                    <h3><strong>@lang('Get in touch')</strong></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-7">
                <p>@lang('If you have any questions or need help, please fill out the form below. We do our best to response within 1 business day')</p>
                <form method="post" action="{{ route('contacts.save') }}" class="contact-form">
                    @csrf
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label label-required">@lang('Fullname')</label>
                                <input type="text" name="fullname" class="form-control" value="{{ old('fullname', '') }}" required />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label label-required">@lang('Phone number')</label>
                                <input type="text" name="phone" class="form-control" value="{{ old('phone', '') }}" required />
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="control-label label-required">@lang('Email address')</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', '') }}" required />
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label label-required">@lang('Subject')</label>
                                <input type="text" name="subject" class="form-control" value="{{ old('subject', '') }}" required />
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="control-label label-required">@lang('Content')</label>
                                <textarea name="content" class="form-control" rows="4" required>{{ old('content', '') }}</textarea>
                            </div>
                        </div>
                        <div class="col-md-12">
                        @if(config('recaptcha.api_site_key') && config('recaptcha.api_secret_key'))
                            <div class="form-group">
                            {!! htmlFormSnippet() !!}
                            @if ($errors->has('g-recaptcha-response'))
                            <div class="text-red mt-1">
                                <small><strong>{{ $errors->first('g-recaptcha-response') }}</strong></small>
                            </div>
                            @endif
                            </div>
                        @endif
                        </div>
                        <div class="col-md-12">
                            @if($errors->any())
                            <div class="alert alert-danger">
                                <ul class="list-unstyled mb-0">
                                    @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                        <div class="col-md-12">
                            <div class="row justify-content-center">
                                <div class="col-auto">
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">@lang('Submit')</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>      
    </div>

@stop

@if(config('recaptcha.api_site_key') && config('recaptcha.api_secret_key'))
    @push('head')
        {!! htmlScriptTagJsApi() !!}
    @endpush
@endif