<div class="form-group">
    <label class="fw-semibold">{{ trans('messages.captcha_engine') }}</label>
    @include('helpers.form_control.select', [
        'name' => 'general[captcha_engine]',
        'value' => Acelle\Model\Setting::get('captcha_engine'),
        'options' =>  array_map(function ($cap) {
            return ['value' => $cap['id'], 'text' => $cap['title']];
        }, \Acelle\Library\Facades\Hook::execute('captcha_method')),
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>