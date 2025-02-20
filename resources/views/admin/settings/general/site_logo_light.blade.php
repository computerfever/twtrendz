<div class="form-group {{ $errors->has('site_logo_light') ? 'has-error' : '' }} control-image">
    <label class="fw-semibold">
        {{ trans('messages.site_logo_light') }}
    </label>
    <div class="row">
        <div class="col-md-9">
            <input value="" type="file" name="general[site_logo_light]" class="form-control file-styled-primary">
        </div>
        <div class="col-md-3">
            <div class="p-3 box-shadow-sm rounded" style="background-color: #333;">
                <img width="100%" src="{{ getSiteLogoUrl('light') }}" />
            </div>
        </div>
    </div>
</div>