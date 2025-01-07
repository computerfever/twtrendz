<h4 class="mb-3">{{ trans('messages.automation.operation.' . request()->operation) }}</h4>
<p>{{ trans('messages.automation.operation.' .request()->operation. '.desc') }}</p>

<input type="hidden" name="options[operation_type]" value="{{ request()->operation }}" />

@php
    $tags = isset($element) ? $element->getOption('tags') : [];
@endphp

<div class="row my-2">
    <div class="col-md-8">
        <div class="form-group">
            <select name="options[tags][]"
                class="select-tag select-search form-control" multiple required>
                @foreach($tags as $tag)
                    <option
                        selected
                        value="{{ $tag }}"
                    >{{ $tag }}</option>
                @endforeach
            </select>
        </div>
    </div>
</div>