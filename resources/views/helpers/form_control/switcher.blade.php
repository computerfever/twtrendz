<label class="checker">
    <input type="hidden" name="{{ $name }}" value="{{ $off_value }}" />
    <input
        type="checkbox"
        name="{{ $name }}"
        value="{{ $on_value }}" class="styled4"
        {{ $value == $on_value ? 'checked' : '' }}

        @if (isset($attributes))
            @foreach ($attributes as $k => $v)
                @if (!in_array($k, ['class']))
                    {{ $k }}="{{ $v }}"
                @endif
            @endforeach
        @endif
    >
    <span class="checker-symbol"></span>
</label>