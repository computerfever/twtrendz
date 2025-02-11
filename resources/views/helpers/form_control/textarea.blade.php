@if (isset($label) && $label != '')
    <label class="form-label">
        {{ $label }}
    </label>
@endif

<textarea
    class="form-control {{ isset($attributes) && isset($attributes['class']) ? $attributes['class'] : ''  }}"
    name="{{ $name }}"

    @if (isset($attributes))
        @foreach ($attributes as $k => $v)
            @if (!in_array($k, ['class']))
                {{ $k }}="{{ $v }}"
            @endif
        @endforeach
    @endif
>{!! isset($value) ? $value : "" !!}</textarea>

@if ($errors->has($name))
    <p class="mb-0 text-danger small mt-1">
        {{ $errors->first($name) }}
    </p>
@endif
