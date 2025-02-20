<div class="form-group">
    <label class="fw-semibold">{{ trans('messages.invoice.format') }}</label>
    @include('helpers.form_control.text', [
        'name' => 'general[invoice.format]',
        'value' => Acelle\Model\Setting::get('invoice.format'),
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>