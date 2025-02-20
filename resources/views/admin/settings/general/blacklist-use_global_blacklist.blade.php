<div class="form-group">
    <div class="d-flex" style="width:100%">
        <div class="me-5">
            <label class="fw-semibold">
                {{ trans('messages.blacklist.use_global_blacklist') }}
            </label>
            <p class="checkbox-description mt-1 mb-0">
                {{ trans('messages.setting.blacklist.use_global_blacklist.help') }}
            </p>
        </div>
            
        <div class="d-flex align-items-top ms-auto">
            <label class="checker">
                <input type="hidden" name="general[blacklist.use_global_blacklist]" value="no" />
                <input
                    type="checkbox"
                    name="general[blacklist.use_global_blacklist]"
                    value="yes" class="styled4"
                    {{ Acelle\Model\Setting::get('blacklist.use_global_blacklist') == 'yes' ? 'checked' : '' }}
                >
                <span class="checker-symbol"></span>
            </label>
        </div>
    </div>
</div>