@include('automation2._back')

<h4 class="mb-3">{{ trans('messages.automation.outgoing_webhook') }}</h4>
<p>{{ trans('messages.automation.action.outgoing-webhook.desc') }}</p>

<div>
    <table class="table border">
        <tbody>
            <tr>
                <th width="50%" class="bg-light fw-normal">{{ trans('messages.automation.outgoing_webhook.request_method') }}</th>
                <td width="50%" class="text-uppercase">
                    {{ $element->get('options')->send_method }}
                </td>
            </tr>
            <tr>
                <th width="50%" class="bg-light fw-normal">{{ trans('messages.automation.outgoing_webhook.authorization_options') }}</th>
                <td width="50%" class="">
                    {{ trans('messages.automation.outgoing_webhook.' . $element->get('options')->authorization_method) }}
                </td>
            </tr>
            <tr>
                <th width="50%" class="bg-light fw-normal">
                    {{ trans('messages.automation.outgoing_webhook.endpoint_url') }}
                </th>
                <td width="50%" class="">
                    {{ $element->get('options')->endpoint_url }}
                </td>
            </tr>
            <tr>
                <th width="50%" class="bg-light fw-normal">
                    {{ trans('messages.automation.outgoing_webhook.headers') }}
                </th>
                <td width="50%" class="">
                    @if ($element->get('options')->header == 'with_headers')
                        <table class="table m-0">
                            <tbody>
                                @foreach ($element->get('options')->headers as $header)
                                    <tr>
                                        <th width="50%" class="fw-normal border-0 py-1 px-2 bg-light">
                                            {{ $header->key ?? 'N/A' }}:
                                        </th>
                                        <td width="50%" class="text-uppercase border-0 py-1 px-2" style="word-break:break-all;">
                                            {{ $header->value ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        {{ trans('messages.automation.outgoing_webhook.no_headers') }}
                    @endif
                </td>
            </tr>
            <tr>
                <th width="50%" class="bg-light fw-normal">
                    {{ trans('messages.automation.outgoing_webhook.unified_body_configuration') }}
                </th>
                <td width="50%" class="">
                    {{ trans('messages.automation.outgoing_webhook.' . $element->get('options')->body_type) }}
                </td>
            </tr>
            @if ($element->get('options')->body_type == 'key_value_pair' && isset($element->get('options')->body_parameters))
                <tr>
                    <th width="50%" class="bg-light fw-normal">
                        {{ trans('messages.automation.outgoing_webhook.body_parameters') }}
                    </th>
                    <td width="50%" class="">
                        @if(isset($element->get('options')->body_parameters))
                            <table class="table m-0">
                                <tbody>
                                    @foreach ($element->get('options')->body_parameters as $param)
                                        <tr>
                                            <th width="50%" class="fw-normal border-0 py-1 px-2 bg-light">
                                                {{ $param->key ?? 'N/A' }}:
                                            </th>
                                            <td width="50%" class="text-uppercase border-0 py-1 px-2" style="word-break:break-all;">
                                                {{ $param->value ?? 'N/A' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </td>
                </tr>
            @endif
            @if ($element->get('options')->body_type == 'plain_text')
                <tr>
                    <th width="50%" class="bg-light fw-normal">
                        {{ trans('messages.automation.outgoing_webhook.plain_text') }}
                    </th>
                    <td width="50%" class="">
                        {!! $element->get('options')->plain_text !!}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>

<div>
    <button webhook-control="save" type="button" class="btn btn-secondary">
        {{ trans('messages.automation.webhook.edit') }}
    </button>
</div>

<div class="mt-4 d-flex py-3">
    <div>
        <h4 class="mb-2">
            {{ trans('messages.automation.dangerous_zone') }}
        </h4>
        <p class="">
            {{ trans('messages.automation.action.delete.wording') }}                
        </p>
        <div class="mt-3">
            <a data-control="webhook-delete" href="javascript:;" data-confirm="{{ trans('messages.automation.action.delete.confirm') }}"
                class="btn btn-secondary">
                <span class="material-symbols-rounded">delete</span> {{ trans('messages.automation.remove_this_action') }}
            </a>
        </div>
    </div>
</div>

<script>
    $(() => {
        new WebhookManager({
            url: '{!! action('Automation2Controller@outgoingWebhookSetup', $automation->uid) !!}?id={{ request()->id }}'
        });
    });

    var WebhookManager = class {
        constructor(options) {
            this.url = options.url;

            //
            this.events();
        }

        getSaveButton() {
            return $('[webhook-control="save"]');
        }

        events() {
            this.getSaveButton().on('click', () => {
                this.showEditPopup();
            });
        }

        showEditPopup() {
            automationPopup.load(this.url);
        }
    }

    $('[data-control="webhook-delete"]').on('click', function(e) {
        e.preventDefault();
        
        var confirm = $(this).attr('data-confirm');
        var dialog = new Dialog('confirm', {
            message: confirm,
            ok: function(dialog) {
                // remove current node
                tree.getSelected().detach();
                
                // save tree
                saveData(function() {
                    // notify
                    notify('success', '{{ trans('messages.notify.success') }}', '{{ trans('messages.automation.action.deteled') }}');
                    
                    // load default sidebar
                    sidebar.load('{{ action('Automation2Controller@settings', $automation->uid) }}');
                });
            },
        });
    });
</script>
