<div class="form-group">
    <label class="fw-semibold">{{ trans('messages.invoice.current') }}</label>
    @include('helpers.form_control.number', [
        'name' => 'general[invoice.current]',
        'value' => Acelle\Model\Setting::get('invoice.current'),
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>