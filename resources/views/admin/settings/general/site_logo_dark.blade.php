<div class="form-group {{ $errors->has('site_logo_dark') ? 'has-error' : '' }} control-image">
    <label class="fw-semibold">
        {{ trans('messages.site_logo_dark') }}
    </label>
    <div class="row">
        <div class="col-md-9">
            <input value="" type="file" name="general[site_logo_dark]" class="form-control file-styled-primary">
        </div>
        <div class="col-md-3">
            
            <div class="p-3 box-shadow-sm rounded" style="background-color: #f6f6f6;">
                <img width="100%" src="{{ getSiteLogoUrl('dark') }}" />
            </div>
            
        </div>
    </div>
</div>