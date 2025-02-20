{!! $content !!}

<link rel="stylesheet" href="{{ AppUrl::asset('core/phoneinput/intlTelInput.css') }}" />
<script src="{{ AppUrl::asset('core/phoneinput/intlTelInput.js') }}"></script>

<script>
    document.addEventListener("DOMContentLoaded", function(event) {
        var content_height = document.body.scrollHeight;
        parent.postMessage(content_height, '*');
    });

    parent.intlTelInput = intlTelInput;
    console.log(document);
</script>