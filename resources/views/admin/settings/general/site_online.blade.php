<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.site_online') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.site_online.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[site_online]',
                'value' => Acelle\Model\Setting::get('site_online'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>