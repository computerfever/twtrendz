@if ($customers->total())
    <a href="{{ action('Admin\CustomerController@index', [
        'keyword' => request()->keyword
    ]) }}" class="search-head border-bottom d-block">
        <div class="d-flex">
            <div class="me-auto">
                <label class="fw-600">
                    <span class="material-symbols-rounded me-1">people </span> {{ trans('messages.customers') }}
                </label>
            </div>
            <div>
                {{ $customers->count() }} / {{ number_with_delimiter($customers->total(), $precision = 0) }} ·
                {{ trans('messages.search.view_all') }}
            </div>
        </div>
    </a>
    @foreach($customers as $customer)
        <a href="{{ action('Admin\CustomerController@edit', $customer->uid) }}" class="search-result border-bottom d-block">
            <div class="d-flex align-items-center">
                <div>
                    <div class="d-flex">
                        <label class="fw-600">
                            {{ $customer->displayName() }}
                        </label>
                    </div>
                </div>
            </div>
        </a>
    @endforeach
@endif