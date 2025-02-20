<link rel="stylesheet" href="{{ AppUrl::asset('core/phoneinput/intlTelInput.css') }}" />
<script src="{{ AppUrl::asset('core/phoneinput/intlTelInput.js') }}"></script>
@php
    $phoneInputId = "phone_" . uniqid();
@endphp
<div>
    <input
        @if (isset($attributes))
            @foreach ($attributes as $k => $v)
                @if (!in_array($k, ['class']))
                    {{ $k }}="{{ $v }}"
                @endif
            @endforeach
        @endif
        id="{{ $phoneInputId }}_Helper" type="text" name="{{ $name }}_Helper" class="form-control {{ isset($attributes) && isset($attributes['class']) ? $attributes['class'] : ''  }}"
        value="{{ $value }}">

    <input id="{{ $phoneInputId }}" type="hidden" name="{{ $name }}" value="{{ $value }}" />
</div>
@if ($errors->has($name))
    <div class="help-block">
        {{ $errors->first($name) }}
    </div>
@endif

<script>
    const phoneInputFieldHelper{{ $phoneInputId }} = document.querySelector("#{{ $phoneInputId }}_Helper");
    const phoneInputField{{ $phoneInputId }} = document.querySelector("#{{ $phoneInputId }}");
    const phoneInput{{ $phoneInputId }} = window.intlTelInput(phoneInputFieldHelper{{ $phoneInputId }}, {
        @if(config('app.locale') == 'ja')
            initialCountry: "jp", // Set Japan as the default country
        @endif
        initialValue: '{{ $value }}',
        utilsScript: "{{ AppUrl::asset('core/phoneinput/utils.js') }}",
    });

    $(function() {
        $(phoneInputField{{ $phoneInputId }}).closest('form').on('submit', function(e) {
            $(phoneInputField{{ $phoneInputId }}).val(phoneInput{{ $phoneInputId }}.getNumber());
        });
    });
</script>
