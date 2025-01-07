@if (Auth::user()->admin->getPermission("setting_general") == 'yes')
    <div class="tab-pane active" id="top-general">
        <div class="row">
            <div class="col-md-12">
                <h2 class="text-semibold">{{ trans('messages.admin.settings.edit_app_settings') }}</h2>
                <h3 class="text-semibold">{{ trans('messages.general') }}</h3>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                @include('admin.settings.general.site_name')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.site_keyword')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.site_logo_light')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.site_logo_dark')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.site_favicon')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.site_online')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.site_offline_message')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.site_description')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.default_language')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.frontend_scheme')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.backend_scheme')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.captcha_engine')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.login_recaptcha')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.embedded_form_recaptcha')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.list_sign_up_captcha')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.enable_user_registration')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.registration_recaptcha')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.custom_script')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.builder')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.invoice-current')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.invoice-format')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.2fa_enable')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.tracking_domain-enable_https')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.email-enable_one_click_unsubscribe_headers')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.campaign-duplicate')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.list-default-double_optin')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.email-default-skip_failed_message')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.signature-enabled')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.blacklist-use_global_blacklist')
            </div>
            <div class="col-md-6">
                @include('admin.settings.general.automation-outgoing_webhook')
            </div>
        </div>
        <div class="row">
            <div class="col-md-12"><p align="right"><a href="{{ action('Admin\SettingController@advanced') }}">{{ trans('messages.configuration.settings') }}</a></p></div>
        </div>
        <div class="text-left">
            <button class="btn btn-secondary"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
        </div>
        
    </div>
@endif
