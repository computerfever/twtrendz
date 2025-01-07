<div class="mb-4">
    <div>
        <h4 class="fw-semibold">{{ trans('messages.automation.outgoing_webhook.test_request') }}</h4>
        <div class="p-3 bg-light border">
            <pre>{{ json_encode($options, JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
</div>

<div class="">
    <div>
        <h4 class="fw-semibold">{{ trans('messages.automation.outgoing_webhook.test_output') }}</h4>
        <div class="p-3 bg-light border">
            <div test-control="output">
                <div class="">
                    <div>
                        <div class="">
                            <div class="">
                                <p><strong>{{ trans('messages.automation.outgoing_webhook.http_repsonse_code') }}:</strong> {{ $result['http_code'] }}</p>
                                <p class="mb-2"><strong>{{ trans('messages.automation.outgoing_webhook.repsonse_body') }}:</strong></p>
                                <pre style="width: 100%;
height: auto;
white-space: inherit;">{{ $result['response'] }}</pre>
                                @if ($result['error'])
                                    <p><strong>{{ trans('messages.automation.outgoing_webhook.error') }}:</strong></p>
                                    <pre class='bg-danger text-white p-3'>{{ $result['error'] }}</pre>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>