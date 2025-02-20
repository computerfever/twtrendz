@if (isset($label) && $label != '')
    <label class="form-label">
        {{ $label }}
    </label>
@endif

<select
    name="{{ $name }}"
    class="select"
    @if (isset($attributes))
        @foreach ($attributes as $k => $v)
            @if (!in_array($k, ['class']))
                {{ $k }}="{{ $v }}"
            @endif
        @endforeach
    @endif
>
	@foreach($options as $option)
		<option
			@if (is_array($value))
				{{ in_array($option['value'], $value) ? " selected" : "" }}
			@else
				{{ in_array($option['value'], explode(",", $value)) ? " selected" : "" }}
			@endif
			value="{{ $option['value'] }}"
		>{{ htmlspecialchars($option['text']) }}</option>
	@endforeach
</select>
