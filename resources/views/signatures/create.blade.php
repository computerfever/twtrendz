@extends('layouts.core.frontend', [
	'menu' => 'signature',
])

@section('title', trans('messages.signature.add_new'))

@section('head')
	<script type="text/javascript" src="{{ AppUrl::asset('core/tinymce/tinymce.min.js') }}"></script>        
    <script type="text/javascript" src="{{ AppUrl::asset('core/js/editor.js') }}"></script>
@endsection

@section('page_header')

	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
			<li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("SignatureController@index") }}">{{ trans('messages.signature.signatures') }}</a></li>
		</ul>
		<h1 class="mc-h1">
			<span class="text-semibold">{{ trans('messages.signature.add_new') }}</span>
		</h1>
	</div>

@endsection

@section('content')

<div class="row">
	<div class="col-sm-12 col-md-10 col-lg-10">
		<p>{!! trans('messages.signature.wording') !!}</p>
	</div>
</div>

<form action="{{ action('SignatureController@store') }}" method="POST" class="">
	@csrf

	@include('signatures._form')
</form>
    
@endsection
