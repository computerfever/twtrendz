<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.setting.automation.outgoing_webhook') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.automation.outgoing_webhook.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            <label class="checker">
                <input type="hidden" name="general[automation.outgoing_webhook]" value="no" />
                <input
                    type="checkbox"
                    name="general[automation.outgoing_webhook]"
                    value="yes" class="styled4"
                    {{ Acelle\Model\Setting::get('automation.outgoing_webhook') == 'yes' ? 'checked' : '' }}
                >
                <span class="checker-symbol"></span>
            </label>
        </div>
    </div>
</div>