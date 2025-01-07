<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.signature.enabled') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.signature.enabled.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[signature.enabled]',
                'value' => Acelle\Model\Setting::get('signature.enabled'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>