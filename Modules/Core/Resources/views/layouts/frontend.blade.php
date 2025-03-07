<!DOCTYPE html>
<html lang="en">
<head>
	@include('layouts.core._head', ["inlcudeModuleCoreCssJs" => true])

	@include('layouts.core._script_vars')

	@yield('head')

	@if (getThemeMode(Auth::user()->customer->theme_mode, request()->session()->get('customer-auto-theme-mode')) == 'dark')
		<meta name="theme-color" content="{{ getThemeColor(
			Auth::user()->customer->getColorScheme()) }}">
	@elseif (Auth::user()->customer->getMenuLayout() == 'left')
		<meta name="theme-color" content="#eff3f5">
	@endif

	<script>
		@if (Auth::user()->customer->theme_mode == 'auto')
			var ECHARTS_THEME = isDarkMode() ? 'dark' : null

			// auto detect dark-mode
			$(function() {
				autoDetechDarkMode('{{ action('AccountController@saveAutoThemeMode') }}');
			});
		@else
			var ECHARTS_THEME = '{{ Auth::user()->customer->theme_mode == 'dark' ? 'dark' : null }}';
		@endif
	</script>

	<!-- Theme -->
  <link rel="stylesheet" type="text/css" href="{{ AppUrl::asset('core/css/theme/'.Auth::user()->customer->getColorScheme().'.css') }}">

</head>
<body class="theme-{{ Auth::user()->customer->getColorScheme() }} {{ Auth::user()->customer->getMenuLayout() }}bar
	{{ Auth::user()->customer->getMenuLayout() }}bar-{{ request()->session()->get('customer-leftbar-state') }} state-{{ request()->session()->get('customer-leftbar-state') }}
	fullscreen-search-box
	mode-{{ getThemeMode(Auth::user()->customer->theme_mode, request()->session()->get('customer-auto-theme-mode'))  }}
">
	@if(config('app.cartpaye'))
		@include('layouts.core._menu_frontend_cartpaye')
    @elseif(config('app.store'))
		@include('layouts.core._menu_frontend_store')
	@elseif(config('app.brand'))
		@include('layouts.core._menu_frontend_brand')
	@elseif (!config('app.saas'))
		@include('layouts.core._menu_frontend_single')
	@else
		@include('layouts.core._menu_frontend_saas')
	@endif

	

	@include('layouts.core._middle_bar')

	<main class="container page-container px-3">
		@include('layouts.core._headbar_frontend')
		
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

	<!-- Admin area -->
	@include('layouts.core._admin_area')

	@if (!config('config.saas'))
		<!-- Admin area -->
		@include('layouts.core._loginas_area')
	@endif

	<!-- Notification -->
	@include('layouts.core._notify')
	@include('layouts.core._notify_frontend')

	<!-- display flash message -->
	@include('layouts.core._flash')

	<div class="modal fade" id="createModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header p-3">
          <h5 class="modal-title fs-5" id="exampleModalLabel">@lang('New Landing Page')</h5>
        	<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="" action="{{route('landingpages.save')}}" method="post" enctype='multipart/form-data'>
          @csrf
          <div class="modal-body p-3">
          	@can("admin_access", Auth::user())
          	<div class="form-group mb-3">
          		<label for="name" class="col-form-label">@lang('Type'):</label>
          		<select name="type" class="form-select" id="type" required>
          			<option value="">Select Type</option>
          			<option value="admin">For All Customers</option>
          			<option value="customer">For Yourself</option>
          		</select>
          	</div>
          	@endif
            <div class="form-group">
              <input type="number" class="form-control" name="template_id" hidden="" required="" id="template_id_builder">
              <label for="name" class="col-form-label">@lang('Name'):</label>
              <input type="text" class="form-control" name="name" required="" id="page-name">
            </div>
          </div>
          <div class="modal-footer p-3">
            <button type="button" class="btn btn-secondary float-end" data-bs-dismiss="modal">@lang('Close')</button>
            <button type="submit" class="btn btn-primary float-end" id="saveandbuilder">@lang('Save & Builder')</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modules Core Javascript File -->
  <script src="{{ Module::asset('core:vendor/tinymce/js/tinymce/tinymce.min.js') }}" ></script>
  <script src="{{ Module::asset('core:app/js/app.js') }}" ></script>

  @stack('scripts')

	<script>
		var wizardUserPopup;

		$(function() {
			// auto detect dark mode


			// Customer color scheme | menu layout wizard
			@if (false)
				$(function() {
					wizardUserPopup = new Popup({
						url: '{{ action('AccountController@wizardColorScheme') }}',
					});
					wizardUserPopup.load();
				});
			@endif
			
			@if (null !== Session::get('orig_admin_id') && Auth::user()->admin)
				notify({
					type: 'warning',
					message: `{!! trans('messages.current_login_as', ["name" => Auth::user()->customer->displayName()]) !!}<br>{!! trans('messages.click_to_return_to_origin_user', ["link" => action("Admin\AdminController@loginBack")]) !!}`,
					timeout: false,
				});
			@endif
		
			@if (null !== Session::get('orig_admin_id') && Auth::user()->admin)
				notify({
					type: 'warning',
					message: `{!! trans('messages.site_is_offline') !!}`,
					timeout: false,
				});
			@endif
		})
			
	</script>

	{!! \Acelle\Model\Setting::get('custom_script') !!}
</body>
</html>