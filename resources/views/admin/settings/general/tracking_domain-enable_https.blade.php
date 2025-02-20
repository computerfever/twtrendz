<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.tracking_domain.enable_https') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.tracking_domain.enable_https.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[tracking_domain.enable_https]',
                'value' => Acelle\Model\Setting::get('tracking_domain.enable_https'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>