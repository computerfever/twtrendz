<div class="form-group">
    <label class="fw-semibold">{{ trans('messages.site_keyword') }}</label>
    @include('helpers.form_control.text', [
        'name' => 'general[site_keyword]',
        'value' => Acelle\Model\Setting::get('site_keyword'),
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>