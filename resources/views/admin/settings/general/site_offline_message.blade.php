<div class="form-group">
    @include('helpers.form_control.textarea', [
        'name' => 'general[site_offline_message]',
        'label' => trans('messages.site_offline_message'),
        'value' => Acelle\Model\Setting::get('site_offline_message'),
        'attributes' => [
            'required' => 'required',
        ],
    ])
</div>