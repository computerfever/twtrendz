<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.notification.on_user_created') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.notification.on_user_created.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[notification.on_user_created]',
                'value' => Acelle\Model\Setting::get('notification.on_user_created'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>