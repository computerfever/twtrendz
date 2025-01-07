<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.campaign.duplicate') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.campaign.duplicate.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            @include('helpers.form_control.switcher', [
                'name' => 'general[campaign.duplicate]',
                'value' => Acelle\Model\Setting::get('campaign.duplicate'),
                'on_value' => 'yes',
                'off_value' => 'no',
            ])
        </div>
    </div>
</div>