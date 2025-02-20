<div class="mb-4">
    <input type="hidden" name="options[type]" value="{{ Acelle\Model\Automation2::TRIGGER_ATTRIBUTE_UPDATE }}" />

    @php
        $fieldUid = $trigger->getOption('field_uid') ?? null;
        $value = $trigger->getOption('value') ?? null;
    @endphp

    <p class="mb-1">{{ trans('messages.automation.trigger.tree.attribute-update.select_field_value') }}</p>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="" class="form-label">{{ trans('messages.field') }}</label>

                <select name="options[field_uid]" data-placeholder="{{ trans('messages.subscriber.choose_a_field') }}"
                    class="select select-search required" required
                >
                    @foreach ($automation->mailList->fields as $field)
                        <option {{ $fieldUid == $field->uid ? 'selected' : '' }} value="{{ $field->uid }}">{{ $field->tag }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="" class="form-label">{{ trans('messages.value') }}</label>

                <input type="text" name="options[value]" value="{{ $value }}" required class="form-control" />
            </div>
        </div>
    </div>
</div>