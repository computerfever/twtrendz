@extends('layouts.popup.small')

@section('content')
	<div class="row">
        <div class="col-md-12">
            <h3 class="mb-3">{{ trans('messages.automation.add_an_action') }}</h3>
            <p>{{ trans('messages.automation.action.intro') }}</p>
                
            <div class="line-list">
                <div action-select="send-an-email" data-key="send-an-email" class="d-flex align-items-center line-item action-select-but action-select-send-an-email">
                    <div class="line-icon">
                        <img width="30px" class="icon-img d-inline-block" src="{{ url('images/icons/email-right.svg') }}" />
                    </div>
                    <div class="line-body">
                        <h5>{{ trans('messages.automation.action.send-an-email') }}</h5>
                        <p>{{ trans('messages.automation.action.send-an-email.desc') }}</p>
                    </div>
                </div>

                <div action-select="wait" data-key="wait" class="d-flex align-items-center line-item action-select-but action-select-send-an-email">
                    <div class="line-icon">
                        <img width="30px" class="icon-img d-inline-block" src="{{ url('images/icons/wait.svg') }}" />
                    </div>
                    <div class="line-body">
                        <h5>{{ trans('messages.automation.action.wait') }}</h5>
                        <p>{{ trans('messages.automation.action.wait.desc') }}</p>
                    </div>
                </div>

                <div action-select="condition" data-key="condition" class="d-flex align-items-center line-item action-select-but action-select-send-an-email {{ $hasChildren == "true"  ? 'd-disabled' : '' }}">
                    <div class="line-icon">
                        <img width="30px" class="icon-img d-inline-block" src="{{ url('images/icons/condition.svg') }}" />
                    </div>
                    <div class="line-body">
                        <h5>{{ trans('messages.automation.action.condition') }}</h5>
                        <p>{{ trans('messages.automation.action.condition.desc') }}</p>
                    </div>
                    @if ($hasChildren == "true")
                        <p class="text-warning small mt-1">
                            <i class="material-symbols-rounded">warning</i> {{ trans('messages.automation.action.can_not_add_condition') }}
                        </p>
                    @endif
                </div>

                <div action-select="operation" data-key="operation" class="d-flex align-items-center line-item action-select-but action-select-send-an-email">
                    <div class="line-icon">
                        <img width="30px" class="icon-img d-inline-block" src="{{ url('images/icons/operation.svg') }}" />
                    </div>
                    <div class="line-body">
                        <h5>{{ trans('messages.automation.action.operation') }}</h5>
                        <p>{{ trans('messages.automation.action.operation.desc') }}</p>
                    </div>
                </div>

                @if (\Acelle\Model\Setting::get('automation.outgoing_webhook') == 'yes')
                    <div action-select="outgoing-webhook" data-key="outgoing-webhook" class="d-flex align-items-center line-item action-select-but action-select-send-an-email">
                        <div class="line-icon">
                            <img width="30px" class="icon-img d-inline-block" src="{{ url('images/icons/webhook.svg') }}" />
                        </div>
                        <div class="line-body">
                            <h5>{{ trans('messages.automation.action.outgoing-webhook') }}</h5>
                            <p>{{ trans('messages.automation.action.outgoing-webhook.desc', [
                                'app_name' => Acelle\Model\Setting::get('site_name'),
                            ]) }}</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <script>
        $(() => {
            $('[action-select="wait"]').click(function() {
                // show select trigger confirm box
                openCreateWaitActionPopup('wait');
            });

            $('[action-select="condition"]').click(function() {
                // show select trigger confirm box
                openCreateConditionActionPopup('condition');
            });

            // Select email element
            $('[action-select="send-an-email"]').click(function() {
                var key = $(this).attr('data-key');

                // new action as email
                var newE = new ElementAction({
                    title: '{{ trans('messages.automation.tree.action_not_set') }}',
                    options: {init: "false"}
                });
                
                // add email to tree
                MyAutomation.addToTree(newE);

                // validate
                newE.validate();

                // save tree
                saveData(function() {
                    // select newly added element
                    doSelectTreeElement(newE);

                    //
                    notify('success', '{{ trans('messages.notify.success') }}', '{{ trans('messages.automation.email.created') }}');
                });
            });

            // select operation element
            $('[action-select="operation"]').on('click', function(e) {
                e.preventDefault();

                var url = '{!! action('Automation2Controller@operationSelect', $automation->uid) !!}';
                automationPopup.load(url);
            });

            // select outgoing-webhook action
            $('[action-select="outgoing-webhook"]').on('click', function(e) {
                e.preventDefault();

                var url = '{!! action('Automation2Controller@outgoingWebhookSetup', $automation->uid) !!}';
                automationPopup.load(url);
            });
        });
        
    </script>

@endsection
