<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.email.enable_one_click_unsubscribe_headers') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.email.enable_one_click_unsubscribe_headers.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[email.enable_one_click_unsubscribe_headers]',
                'value' => Acelle\Model\Setting::get('email.enable_one_click_unsubscribe_headers'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>