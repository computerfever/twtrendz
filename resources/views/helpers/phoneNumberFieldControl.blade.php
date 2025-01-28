@php
    $value = isset($values[$field->tag]) ? $values[$field->tag] : $field->default_value;
@endphp
<link rel="stylesheet" href="{{ AppUrl::asset('core/phoneinput/intlTelInput.css') }}" />
<script src="{{ AppUrl::asset('core/phoneinput/intlTelInput.js') }}"></script>

<div class="form-group {{ $errors->has('credits') ? 'has-error' : '' }}">
    <label>
        {{ $field->label }}
    </label>
    <div>
        <input
            {{ isset($list->getFieldRules()[$field->tag]) && $list->getFieldRules()[$field->tag] == 'required' ? 'required' : '' }}
            id="phone_{{ $field->uid }}_helper" type="text" name="{{ $field->tag }}_Helper" class="form-control"
            value="{{ $value }}">

        <input id="phone_{{ $field->uid }}" type="hidden" name="{{ $field->tag }}" value="{{ $value }}" />
    </div>
    @if ($errors->has($field->tag))
        <div class="help-block">
            {{ $errors->first($field->tag) }}
        </div>
    @endif
</div>

<script>
    const phoneInputFieldHelper = document.querySelector("#phone_{{ $field->uid }}_helper");
    const phoneInputField = document.querySelector("#phone_{{ $field->uid }}");
    const phoneInput = window.intlTelInput(phoneInputFieldHelper, {
        initialValue: '{{ $value }}',
        utilsScript: "{{ AppUrl::asset('core/phoneinput/utils.js') }}",
    });

    $(function() {
        $(phoneInputField).closest('form').on('submit', function(e) {
            $(phoneInputField).val(phoneInput.getNumber());
        });
    });
</script>
