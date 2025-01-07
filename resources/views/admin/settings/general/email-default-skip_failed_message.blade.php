<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.email.default.skip_failed_message') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.email.default.skip_failed_message.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[email.default.skip_failed_message]',
                'value' => Acelle\Model\Setting::get('email.default.skip_failed_message'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>