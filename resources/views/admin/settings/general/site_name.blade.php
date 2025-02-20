<div class="form-group">
    <label class="fw-semibold">{{ trans('messages.site_name') }}</label>
    @include('helpers.form_control.text', [
        'name' => 'general[site_name]',
        'value' => Acelle\Model\Setting::get('site_name'),
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>