<h4>{{ trans('messages.email_verification_plan.details') }}</h4>
<div class="mb-4">
    <div class="mb-3">
        <label class="form-label">{{ trans('messages.email_verification_plan.plan_name') }}</label>
        <input type="text" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}"
            name="name"
            value="{{ $plan->name }}"
        >
    </div>

    <div class="mb-3">
        <label class="form-label">{{ trans('messages.email_verification_plan.description') }}</label>
        <input type="text" class="form-control {{ $errors->has('description') ? 'is-invalid' : '' }}"
            name="description"
            value="{{ $plan->description }}"
        >
    </div>

    <div class="mb-3">
        <label class="form-label">{{ trans('messages.email_verification_plan.credits') }}</label>
        <input type="number" class="form-control {{ $errors->has('credits') ? 'is-invalid' : '' }}"
            name="credits"
            value="{{ $plan->credits }}"
        >
    </div>

    <div class="mb-3">
        <label class="form-label">{{ trans('messages.price') }}</label>
        <input type="number" class="form-control {{ $errors->has('price') ? 'is-invalid' : '' }}"
            name="price"
            value="{{ $plan->getPrice() }}"
        >
    </div>

    <div class="mb-3">
        <label class="form-label">{{ trans('messages.currency') }}</label>
        <select class="form-select dropdownfrequency select" name="currency_id">
            @foreach (Acelle\Model\Currency::getSelectOptions() as $option)
                <option value="{{ $option['value'] }}">{{ $option['text'] }}</option>
            @endforeach
        </select>
    </div>
</div>