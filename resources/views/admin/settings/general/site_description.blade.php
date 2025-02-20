<div class="form-group">
    <label class="fw-semibold">{{ trans('messages.site_description') }}</label>
    @include('helpers.form_control.textarea', [
        'name' => 'general[site_description]',
        'value' => Acelle\Model\Setting::get('site_description'),
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>