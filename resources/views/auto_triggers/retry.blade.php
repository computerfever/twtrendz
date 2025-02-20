@extends('layouts.popup.small')

@section('content')
        
                
<p class="mb-2">{!! trans('messages.automation.action.error_intro', [
    'email' => $trigger->subscriber->email,
]) !!}</p>

<pre class="bg-light p-2"><code>{{ $action->getProgressDescription($trigger->automation2->customer->timezone, $trigger->automation2->customer->getLanguageCode()) }}</code></pre>

<div>
    <p class="mb-2">{{ trans('messages.automation.action.retry.confirm') }}</p>
</div>

<div class="mt-4 text-center">
    <button data-action="do-retry" data-url="{{ action('AutoTrigger@retry', [
        'action_id' => request()->action_id,
        'auto_trigger_id' => request()->auto_trigger_id,
    ]) }}" type="button" class="btn btn-secondary px-3 me-2">{{ trans('messages.automation.action.retry') }}</button>
    <button type="button" class="btn btn-link fw-600" data-bs-dismiss="modal">{{ trans('messages.cancel') }}</button>
</div>

<script>
    $(function() {
        $('[data-action="do-retry"]').each(function() {
            new DoActionRetry({
                button: $(this),
            });
        });
        
    });

    var DoActionRetry = class {
        constructor(options) {
            var _this = this;
            this.button = options.button;
            this.url = this.button.attr('data-url');

            // event
            this.button.on('click', function() {
                new Dialog('confirm', {
                    message: '{{ trans('messages.automation.action.retry.confirm') }}',
                    ok: function() {
                        _this.retry();
                    }
                });
            });
        }

        retry() {
            addMaskLoading();
            $.ajax({
                url: this.url,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                }
            }).done(function(response) {
                //
                notify('success', '{{ trans('messages.notify.success') }}',
                    response.message);

                window.currentRetry.popup.hide();
                listContact.load();
            }).fail(function(response) {
                new Dialog('alert', {
                    title: '{{ trans('messages.notify.error') }}',
                    message: response.responseText,
                });

                removeMaskLoading();
            }).always(function() {
                removeMaskLoading();
            });
        }
    }
</script>

@endsection

