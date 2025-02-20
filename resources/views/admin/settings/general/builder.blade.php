<div class="form-group">
    <label class="fw-semibold">{{ trans('messages.builder') }}</label>
    @include('helpers.form_control.select', [
        'name' => 'general[builder]',
        'value' => Acelle\Model\Setting::get('builder'),
        'options' => [
            ['value' => 'both', 'text' => trans('messages.builder.both')],
            ['value' => 'pro', 'text' => trans('messages.builder.pro')],
            ['value' => 'classic', 'text' => trans('messages.builder.classic')],
        ],
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>