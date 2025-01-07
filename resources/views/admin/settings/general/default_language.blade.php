<div class="form-group">
    <label class="fw-semibold">{{ trans('messages.default_language') }}</label>
    @include('helpers.form_control.select', [
        'name' => 'general[default_language]',
        'value' => Acelle\Model\Setting::get('default_language'),
        'options' => Acelle\Model\Language::getSelectOptions(),
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>