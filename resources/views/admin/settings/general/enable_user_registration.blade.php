<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.enable_user_registration') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.enable_user_registration.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[enable_user_registration]',
                'value' => Acelle\Model\Setting::get('enable_user_registration'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>