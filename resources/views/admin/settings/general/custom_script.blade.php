<div class="form-group">
    @include('helpers.form_control.textarea', [
        'name' => 'general[custom_script]',
        'label' => trans('messages.custom_script'),
        'value' => Acelle\Model\Setting::get('custom_script'),
    ])
</div>