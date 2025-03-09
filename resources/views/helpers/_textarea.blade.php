<textarea 
    {{ isset($readonly) ? "readonly='readonly'" : "" }}
    type="text"
    name="{{ $name }}"
    rows="{{ isset($rows) ? $rows : "3" }}"
    class="form-control{{ $classes }} {{ isset($class) ? $class : "" }}"
    {{ isset($disabled) && $disabled == true ? ' disabled="disabled"' : "" }}
>{{ isset($value) ? $value : "" }}</textarea>
