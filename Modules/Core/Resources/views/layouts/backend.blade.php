<!DOCTYPE html>
<html lang="en">
<head>
	@include('layouts.core._head')

	<!-- <link rel="stylesheet" href="{{ Module::asset('core:core/core.css') }}"> -->
	<!-- <link rel="stylesheet" href="{{ Module::asset('core:app/css/customize.css') }}"> -->

	@include('layouts.core._favicon')

	@include('layouts.core._script_vars')

	@yield('head')

	@if (getThemeMode(Auth::user()->admin->theme_mode, request()->session()->get('admin-auto-theme-mode')) == 'dark')
		<meta name="theme-color" content="{{ getThemeColor(
			Auth::user()->admin->getColorScheme(),
			request()->session()->get('admin-auto-theme-mode')
		) }}">
	@elseif (Auth::user()->admin->getMenuLayout() == 'left')
		<meta name="theme-color" content="#eff3f5">
	@endif

	<script>
		@if (Auth::user()->admin->theme_mode == 'auto')
			var ECHARTS_THEME = isDarkMode() ? 'dark' : null

			// auto detect dark-mode
			$(function() {
				autoDetechDarkMode('{{ action('Admin\AccountController@saveAutoThemeMode') }}');
			});
		@else
			var ECHARTS_THEME = '{{ Auth::user()->admin->theme_mode == 'dark' ? 'dark' : null }}';
		@endif
	</script>

	<!-- Theme -->
  	<link rel="stylesheet" type="text/css" href="{{ AppUrl::asset('core/css/theme/'.Auth::user()->customer->getColorScheme().'.css') }}">

</head>
<body class="{{ isset($body_class) ? $body_class : '' }} theme-{{ Auth::user()->admin->getColorScheme() }} {{ Auth::user()->admin->getMenuLayout() }}bar
	{{ Auth::user()->admin->getMenuLayout() }}bar-{{ request()->session()->get('admin-leftbar-state') }} state-{{ request()->session()->get('admin-leftbar-state') }}
	
	mode-{{ getThemeMode(Auth::user()->admin->theme_mode, request()->session()->get('admin-auto-theme-mode')) }}
">
	
	@if (config('app.saas'))
		@include('layouts.core._menu_backend')
	@else
		@include('layouts.core._menu_single')
	@endif

	@include('layouts.core._middle_bar')

	<main class="container page-container px-3">
		@include('layouts.core._headbar_backend')
		
		@yield('page_header')

		<!-- display flash message -->
		@include('layouts.core._errors')

		<!-- landingpage modules success and errors alert flashdata -->
			@if (session('success'))
			<div class="alert alert-success" role="alert">
			  <i class="fas fa-check-circle text-success mr-1"></i> {!! session('success') !!}
			</div>
			@endif

			@if (session('error'))
			<div class="alert alert-danger">
			  <i class="fas fa-times text-danger mr-2"></i> {!! session('error') !!}
			</div>
			@endif

		<!-- main inner content -->
		@yield('content')

		<!-- Footer -->
		@include('layouts.core._footer')
	</main>

	<!-- Notification -->
	@include('layouts.core._notify')
    @include('layouts.core._notify_backend')

	<!-- Admin area -->
	@include('layouts.core._loginas_area')

	<!-- display flash message -->
	@include('layouts.core._flash')

	{!! \Acelle\Model\Setting::get('custom_script') !!}
</body>
</html>