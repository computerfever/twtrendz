<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.2fa_enable') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.2fa_enable.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            <label class="checker">
                <input type="hidden" name="general[2fa_enable]" value="no" />
                <input
                    type="checkbox"
                    name="general[2fa_enable]"
                    value="yes" class="styled4"
                    {{ Acelle\Model\Setting::get('2fa_enable') == 'yes' ? 'checked' : '' }}
                >
                <span class="checker-symbol"></span>
            </label>
        </div>
    </div>
</div>