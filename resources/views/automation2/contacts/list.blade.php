@if ($contacts->count() > 0)
    <style type="text/css">
        .action-error {
            background: #e77070;
        }
    </style>

    <p class="insight-intro mb-2 small">
        {{ trans('messages.automation.contact.all_count', ['count' => number_with_delimiter($contacts->total(), $precision = 0)]) }}
    </p>
        
    <div class="mc-table small border-top">
        @foreach ($contacts as $key => $contact)
            @php
                $trigger = \Acelle\Model\AutoTrigger::find($contact->auto_trigger_id);
            @endphp
            <div class="mc-row d-flex align-items-center">
                <div class="media trigger">
                    <a href="javascript:;" onclick="automationPopup.load('{{ action('Automation2Controller@profile', [
                        'uid' => $automation->uid,
                        'contact_id' => is_null($contact->id) ? '#' : $contact->id,
                    ]) }}')" class="font-weight-semibold d-block">
                        @if(isSiteDemo() || is_null($contact->id))
                            <img src="https://i.pravatar.cc/300" />
                        @else
                            <img src="{{ action('SubscriberController@avatar',  $contact->id) }}" />
                        @endif
                    </a>
                </div>
                <div class="flex-fill" style="width: 20%">
                    @if (is_null($contact->id))
                        <a href="javascript:;" class="font-weight-semibold d-block">
                            [moved or deleted]
                        </a>
                    @else
                        <a href="javascript:;" onclick="automationPopup.load('{{ action('Automation2Controller@profile', [
                            'uid' => $automation->uid,
                            'contact_id' => $contact->id,
                        ]) }}')" class="font-weight-semibold d-block">
                            {{ $contact->getFullName($default = null, $reload = true) }}
                        </a>
                    @endif

                    <desc>{{ is_null($contact->id) ? $trigger->getSubscriberCachedInfo('email') : $contact->email }}</desc>
                </div>
                
                <div class="actions-points">
                    @if (!is_null($trigger))
                        @php
                            $points = $trigger->getExecutedActions();
                        @endphp

                        @if (empty($points))
                            <span>{{ trans('messages.automation.status.triggered.desc') }}</span>
                        @endif

                        @foreach ($points as $action)
                            @php
                                if ($action->getOption('error')) {
                                    $style = 'action-error';
                                } else {
                                    $style = "action-{$action->getType()}";
                                }
                            @endphp
                            <span
                                @if($action->getOption('error'))
                                    data-action="retry"
                                    data-url="{{ action('AutoTrigger@retry', [
                                        'action_id' => $action->getId(),
                                        'auto_trigger_id' => $trigger->id,
                                    ]) }}"
                                @endif
                                class="xtooltip round-action-point {{ $style }}" title="{{ $action->getProgressDescription($customer->timezone, $customer->getLanguageCode()) }}">
                            </span>
                        @endforeach
                    @endif
                </div>
                <div class="flex-fill text-end">
                    @if (is_null($trigger))
                        <label title="" class="text-end">
                            <span class="">
                                <span class="text-warning">{{ trans('messages.automation.contacts.trigger.waiting') }}</span>
                            </span>
                        </label>
                        <desc>
                            <a target="_blank" style="color:blue;text-decoration: underline;"
                                href="{{ action('Automation2Controller@triggerNow', [ 'automation' => $automation->uid, 'subscriber' => $contact->id ]) }}"
                                class="timeline-trigger-now">
                                {{ trans('messages.automation.trigger_now') }}
                            </a>
                        </desc>
                    @elseif (is_null($contact->id))
                        <desc>{{ trans('messages.automation.last_activity') }} •
                            <a target="_blank" style="color:blue;text-decoration: underline;"
                                href="#"
                                class="trigger-check"
                            >
                                {{ trans('messages.trigger.check') }}
                            </a>
                        </desc>
                    @else
                        <desc>{{ trans('messages.automation.last_activity') }} •
                            <a target="_blank" style="color:blue;text-decoration: underline;"
                                href="{{ action('AutoTrigger@check', [ 'id' => $trigger->id ]) }}"
                                class="trigger-check"
                            >
                                {{ trans('messages.trigger.check') }}
                            </a>
                        </desc>
                    @endif
                    
                </div>
            </div>
        @endforeach
        
    @include('helpers._pagination', ['paginator' => $contacts] )
@else
    <div class="empty-list">
        <i class="lnr lnr-users"></i>
        <span class="line-1">
            {{ trans('messages.automation.empty_contacts') }}
        </span>
    </div>
@endif

<script>
    $(function() {
        $('[data-action="retry"]').each(function() {
            new ActionRetry({
                button: $(this),
            });
        });

    });

    var ActionRetry = class {
        constructor(options) {
            var _this = this;
            this.button = options.button;
            this.url = this.button.attr('data-url');
            this.popup = new Popup({
                url: this.url,
            });

            // event
            this.button.on('click', function() {
                _this.load();
            });
        }

        load() {
            window.currentRetry = this;
            this.popup.load();
        }
    }
</script>

<script>
    $('.timeline-trigger-now').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');

        var dia = new Dialog('confirm', {
            message: `{{ trans('messages.automation.trigger_now.confirm') }}`,
            ok: function() {
                $(this).addClass('link-disabled');
                addMaskLoading();

                $.ajax({
                    url: url,
                    method: 'POST',
                    success: function (response) {
                        // notify
                        // notify(response.status, '{{ trans('messages.notify.success') }}', response.message); 

                        listContact.load();

                        removeMaskLoading();
                    }
                });
            }
        });
    });

    $('.trigger-check').on('click', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');

        $(this).addClass('link-disabled');
        addMaskLoading();

        $.ajax({
            url: url,
            method: 'GET',
            globalError: false,
            success: function (response) {
                // notify
                // notify(response.status, '{{ trans('messages.notify.success') }}', response.message); 

                listContact.load();
            }
        }).fail((res) => {
            // notify
            // notify('error', '{{ trans('messages.notify.error') }}', res.responseText);

            new Dialog('alert', {
                title: LANG_ERROR,
                message: res.responseText,
            });

            $(this).removeClass('link-disabled');
        })
        .always(function() {
            removeMaskLoading();
        });
    });
</script>