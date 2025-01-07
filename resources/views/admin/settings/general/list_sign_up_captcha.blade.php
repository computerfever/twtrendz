<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.list_sign_up_captcha') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.list_sign_up_captcha.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[list_sign_up_captcha]',
                'value' => Acelle\Model\Setting::get('list_sign_up_captcha'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>