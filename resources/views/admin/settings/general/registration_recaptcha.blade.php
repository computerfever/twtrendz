<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.registration_recaptcha') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.registration_recaptcha.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[registration_recaptcha]',
                'value' => Acelle\Model\Setting::get('registration_recaptcha'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>