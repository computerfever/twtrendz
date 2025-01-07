@php
    $value = isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value;
@endphp
<link rel="stylesheet" href="{{ AppUrl::asset('core/phoneinput/intlTelInput.css') }}" />
<script src="{{ AppUrl::asset('core/phoneinput/intlTelInput.js') }}"></script>

<div class="form-group {{ $errors->has('credits') ? 'has-error' : '' }}">
    <label>
        {{ $field->label }}
    </label>
    
    @include('helpers.form_control.phone', [
        'name' => $field->tag,
        'value' => $value,
        'attributes' => [
            'required' => isset($list->getFieldRules()[$field->tag]) && $list->getFieldRules()[$field->tag] == 'required' ? 'required' : '',
        ],
    ])
</div>