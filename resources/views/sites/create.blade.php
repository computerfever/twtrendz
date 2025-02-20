@extends('layouts.core.frontend', [
	'menu' => 'site',
])

@section('title', trans('messages.site.add_new'))

@section('page_header')

	<div class="page-title">
		<ul class="breadcrumb breadcrumb-caret position-right">
			<li class="breadcrumb-item"><a href="{{ action("HomeController@index") }}">{{ trans('messages.home') }}</a></li>
            <li class="breadcrumb-item"><a href="{{ action("SiteController@index") }}">{{ trans('messages.site.landing_pages') }}</a></li>
		</ul>
		<h1 class="mc-h1">
			<span class="text-semibold">{{ trans('messages.site.add_new') }}</span>
		</h1>
	</div>

@endsection

@section('content')

@include('sites._menu')

<div class="row">
	<div class="col-sm-12 col-md-10 col-lg-10">
		<p>{!! trans('messages.site.create.intro') !!}</p>
	</div>
</div>

<form action="{{ action('SiteController@store') }}" method="POST" class="">
	@csrf

	@include('sites._form')
</form>
    
@endsection
