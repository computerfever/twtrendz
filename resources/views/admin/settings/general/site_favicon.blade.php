<div class="form-group {{ $errors->has('site_favicon') ? 'has-error' : '' }} control-image">
    <label class="fw-semibold">
        {{ trans('messages.site_favicon') }}
    </label>
    <div class="row">
        <div class="col-md-9">
            <input value="" type="file" name="general[site_favicon]" class="form-control file-styled-primary">
        </div>
        <div class="col-md-3">
            @if (Acelle\Model\Setting::get('site_favicon', false))
                <div class="p-3 box-shadow-sm rounded text-center" style="background-color: #f6f6f6;">
                    <img width="100%" src="{{ action('SettingController@file', Acelle\Model\Setting::get('site_favicon')) }}" />
                </div>
            @endif
            
        </div>
    </div>
</div>