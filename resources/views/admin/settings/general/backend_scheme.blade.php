<div class="form-group">
    <label class="fw-semibold">{{ trans('messages.backend_scheme') }}</label>
    @include('helpers.form_control.select', [
        'name' => 'general[backend_scheme]',
        'value' => Acelle\Model\Setting::get('backend_scheme'),
        'options' => array_map(function($color) {
            return ['value' => $color, 'text' => trans('messages.' . $color)];
        }, config('default.frontend_scheme')['options']),
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>