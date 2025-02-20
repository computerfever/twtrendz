@extends('layouts.popup.large')

@section('content')
    @php
        $formId = 'WebhookForm' . uniqid();
    @endphp

    <div class="row">
        <div class="col-md-12">
            <div class="">
                <form id="{{ $formId }}" action="{{ action('Automation2Controller@outgoingWebhookSave', $automation->uid) }}"
                    method="POST">
                    {{ csrf_field() }}

                    @if ($element)
                        <input type="hidden" name="id" value="{{ request()->id }}" />
                    @endif

                    <h2 class="text-semibold">{{ trans('messages.automation.outgoing_webhook.setup') }}</h2>

                    <p>{!! trans('messages.automation.outgoing_webhook.wording', [
                        'app_name' => Acelle\Model\Setting::get('site_name'),
                    ]) !!}</p>

                    <div class="p-3 border bg-light">
                        <div class="mb-4 p-3 border rounded shadow-sm bg-white">
                            <label for=""
                                class="form-label fw-semibold">{{ trans('messages.automation.outgoing_webhook.request_method') }}</label>
                            <div class="d-flex align-items-center">
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input {{ $options['send_method'] == 'get' ? 'checked' : '' }} type="radio" name="webhook[send_method]" value="get" id="method-get"
                                            class="styled" />
                                        <label class="check-symbol" for="method-get"></label>
                                    </div>
                                    <div>
                                        <label for="method-get"
                                            class="mb-0">{{ trans('messages.automation.outgoing_webhook.get_method') }}</label>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input {{ $options['send_method'] == 'post' ? 'checked' : '' }} type="radio" name="webhook[send_method]" value="post"
                                            id="method-post" class="styled" />
                                        <label class="check-symbol" for="method-post"></label>
                                    </div>
                                    <label for="method-post"
                                        class="mb-0">{{ trans('messages.automation.outgoing_webhook.post_method') }}</label>
                                </div>
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input {{ $options['send_method'] == 'put' ? 'checked' : '' }} type="radio" name="webhook[send_method]" value="put" id="method-put"
                                            class="styled" />
                                        <label class="check-symbol" for="method-put"></label>
                                    </div>
                                    <label for="method-put"
                                        class="mb-0">{{ trans('messages.automation.outgoing_webhook.put_method') }}</label>
                                </div>
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input {{ $options['send_method'] == 'delete' ? 'checked' : '' }} type="radio" name="webhook[send_method]" value="delete" id="method-delete"
                                            class="styled" />
                                        <label class="check-symbol" for="method-delete"></label>
                                    </div>
                                    <label for="method-delete"
                                        class="mb-0">{{ trans('messages.automation.outgoing_webhook.delete_method') }}</label>
                                </div>
                            </div>
                        </div>

                        <div data-control="authorization-options" class="mb-4 p-3 border rounded shadow-sm bg-white">
                            <label for=""
                                class="form-label fw-semibold">{{ trans('messages.automation.outgoing_webhook.authorization_options') }}</label>
                            <div class="d-flex align-items-center mb-3">
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input {{ $options['authorization_method'] == 'bearer_token' ? 'checked' : '' }} data-control="method" type="radio" name="webhook[authorization_method]"
                                            value="bearer_token" id="method-bearer_token" class="styled" />
                                        <label class="check-symbol" for="method-bearer_token"></label>
                                    </div>
                                    <div>
                                        <label for="method-bearer_token"
                                            class="mb-0">{{ trans('messages.automation.outgoing_webhook.bearer_token') }}</label>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input {{ $options['authorization_method'] == 'basic_auth' ? 'checked' : '' }} data-control="method" checked type="radio"
                                            name="webhook[authorization_method]" value="basic_auth" id="method-basic_auth"
                                            class="styled" />
                                        <label class="check-symbol" for="method-basic_auth"></label>
                                    </div>
                                    <label for="method-basic_auth"
                                        class="mb-0">{{ trans('messages.automation.outgoing_webhook.basic_auth') }}</label>
                                </div>
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input {{ $options['authorization_method'] == 'custom' ? 'checked' : '' }} data-control="method" type="radio" name="webhook[authorization_method]"
                                            value="custom" id="method-custom" class="styled" />
                                        <label class="check-symbol" for="method-custom"></label>
                                    </div>
                                    <label for="method-custom"
                                        class="mb-0">{{ trans('messages.automation.outgoing_webhook.custom') }}</label>
                                </div>
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input {{ $options['authorization_method'] == 'no_auth' ? 'checked' : '' }} data-control="method" type="radio" name="webhook[authorization_method]"
                                            value="no_auth" id="method-no_auth" class="styled" />
                                        <label class="check-symbol" for="method-no_auth"></label>
                                    </div>
                                    <label for="method-no_auth"
                                        class="mb-0">{{ trans('messages.automation.outgoing_webhook.no_auth') }}</label>
                                </div>
                            </div>

                            <div data-option-box="bearer_token">
                                <hr>
                                <p class="small mb-2">
                                    {{ trans('messages.automation.outgoing_webhook.bearer_token.desc') }}</p>
                                <table class="table table-bordered m-0">
                                    <tr>
                                        <th width="10%" valign="middle" class="small bg-light text-nowrap px-3"
                                            style="font-weight:normal;">
                                            {{ trans('messages.automation.outgoing_webhook.bearer_token') }}
                                        </th>
                                        <td>
                                            <input data-option-input="required" type="text" name="webhook[authorization][bearer_token]" value="{{ $options['authorization']['bearer_token'] }}"
                                                class="form-control" />
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div data-option-box="basic_auth">
                                <hr>
                                <p class="small mb-2">{{ trans('messages.automation.outgoing_webhook.basic_auth.desc') }}
                                </p>
                                <table class="table table-bordered m-0">
                                    <tr>
                                        <th width="10%" valign="middle" class="small bg-light text-nowrap px-3"
                                            style="font-weight:normal;">
                                            {{ trans('messages.automation.outgoing_webhook.username') }}
                                        </th>
                                        <td>
                                            <input data-option-input="required" type="text" name="webhook[authorization][username]" value="{{ $options['authorization']['username'] }}"
                                                class="form-control" />
                                        </td>
                                        <th width="10%" valign="middle" class="small bg-light text-nowrap px-3"
                                            style="font-weight:normal;">
                                            {{ trans('messages.automation.outgoing_webhook.password') }}
                                        </th>
                                        <td>
                                            <input data-option-input="required" type="text" name="webhook[authorization][password]" value="{{ $options['authorization']['password'] }}"
                                                class="form-control" />
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div data-option-box="custom">
                                <hr>
                                <p class="small mb-2">{{ trans('messages.automation.outgoing_webhook.custom.desc') }}</p>
                                <table class="table table-bordered m-0">
                                    <tr>
                                        <th width="10%" valign="middle" class="small bg-light text-nowrap px-3"
                                            style="font-weight:normal;">
                                            {{ trans('messages.automation.outgoing_webhook.custom_key') }}
                                        </th>
                                        <td>
                                            <input data-option-input="required" type="text" name="webhook[authorization][custom_key]" value="{{ $options['authorization']['custom_key'] }}"
                                                class="form-control" />
                                        </td>
                                        <th width="10%" valign="middle" class="small bg-light text-nowrap px-3"
                                            style="font-weight:normal;">
                                            {{ trans('messages.automation.outgoing_webhook.custom_value') }}
                                        </th>
                                        <td>
                                            <div data-control="input-with-tag" class="input-group">
                                                <input tag-control="input" type="text" name="webhook[authorization][custom_value]"
                                                    value="{{ $options['authorization']['custom_value'] }}"
                                                    class="form-control" />
                                                <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                                    data-bs-toggle="dropdown" aria-expanded="false">...</button>
                                                <ul class="dropdown-menu">
                                                    @foreach ($automation->getAvailableOutgoingWebhookTags() as $tag)
                                                        <li>
                                                            <a tag-control="selector" data-tag="{{ $tag['tag'] }}" class="dropdown-item border-bottom" href="#">
                                                                <span class="fw-semibold">{{ $tag['label'] }}</span><br>
                                                                <span>{{ $tag['tag'] }}</span>
                                                            </a>
                                                        </li>
                                                    @endforeach
                                                </ul>

                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div data-option-box="no_auth">
                                <hr>
                                <p class="small mb-0">{{ trans('messages.automation.outgoing_webhook.no_auth.desc') }}</p>
                            </div>
                        </div>

                        <div class="mb-4 p-3 border rounded shadow-sm bg-white">
                            <label for=""
                                class="form-label fw-semibold">{{ trans('messages.automation.outgoing_webhook.endpoint_url') }}</label>
                            <div class="d-flex align-items-center">
                                <div data-control="input-with-tag" class="input-group">
                                    <input tag-control="input" type="url" name="webhook[endpoint_url]" value="{{ $options['endpoint_url'] }}" class="form-control" required />
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">...</button>
                                    <ul class="dropdown-menu">
                                        @foreach ($automation->getAvailableOutgoingWebhookTags() as $tag)
                                            <li>
                                                <a tag-control="selector" data-tag="{{ $tag['tag'] }}" class="dropdown-item border-bottom" href="#">
                                                    <span class="fw-semibold">{{ $tag['label'] }}</span><br>
                                                    <span>{{ $tag['tag'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div data-control="headers" class="mb-4 p-3 border rounded shadow-sm bg-white">
                            <label for=""
                                class="form-label fw-semibold">{{ trans('messages.automation.outgoing_webhook.headers') }}</label>
                            <div class="d-flex align-items-center">
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input data-control="checker" type="radio" name="webhook[header]"
                                            {{ $options['header'] == 'no_headers' ? 'checked' : '' }}
                                            value="no_headers" id="header-no_headers" class="styled" />
                                        <label class="check-symbol" for="header-no_headers"></label>
                                    </div>
                                    <div>
                                        <label for="header-no_headers"
                                            class="mb-0">{{ trans('messages.automation.outgoing_webhook.no_headers') }}</label>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center me-4">
                                    <div class="me-2">
                                        <input data-control="checker" type="radio" name="webhook[header]"
                                            {{ $options['header'] == 'with_headers' ? 'checked' : '' }}
                                            value="with_headers" id="header-with_headers" class="styled" />
                                        <label class="check-symbol" for="header-with_headers"></label>
                                    </div>
                                    <div>
                                        <label for="header-with_headers"
                                            class="mb-0">{{ trans('messages.automation.outgoing_webhook.with_headers') }}</label>
                                    </div>
                                </div>
                            </div>

                            <div data-option-box="with_headers" class="mt-3">
                                <div data-control="header-list-container">
                                    <table class="table table-bordered m-0">
                                        <thead>
                                            <tr>
                                                <th width="50%" class="small" style="font-weight:normal;">
                                                    {{ trans('messages.automation.outgoing_webhook.header_key') }}</th>
                                                <th width="50%" class="small" style="font-weight:normal;">
                                                    {{ trans('messages.automation.outgoing_webhook.header_value') }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody list-control="content">

                                        </tbody>
                                    </table>
                                    <div list-control="add" class="text-end mt-2">
                                        <button type="button" class="btn btn-primary btn-sm">
                                            <span class="d-flex align-items-center">
                                                <span class="material-symbols-rounded me-1">
                                                    add
                                                </span>
                                                <span>
                                                    {{ trans('messages.automation.outgoing_webhook.add_more_header') }}
                                                </span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div data-control="body-config" class="form-group mb-0 p-3 border rounded shadow-sm bg-white">
                            <label for=""
                                class="form-label fw-semibold">{{ trans('messages.automation.outgoing_webhook.unified_body_configuration') }}</label>
                            <div class="row mb-2">
                                <div class="col-md-6">
                                    <select data-control="selector" name="webhook[body_type]" id=""
                                        class="select">
                                        <option {{ $options['body_type'] == 'key_value_pair' ? 'selected' : '' }} value="key_value_pair">
                                            {{ trans('messages.automation.outgoing_webhook.key_value_pair') }}</option>
                                        <option {{ $options['body_type'] == 'plain_text' ? 'selected' : '' }} value="plain_text">
                                            {{ trans('messages.automation.outgoing_webhook.plain_text') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div data-option-box="key_value_pair" class="">
                                <div data-control="body-parameters-container">
                                    <label for=""
                                        class="form-label fw-semibold">{{ trans('messages.automation.outgoing_webhook.body_parameters') }}</label>
                                    <table class="table table-bordered m-0">
                                        <thead>
                                            <tr>
                                                <th width="50%" class="small" style="font-weight:normal;">
                                                    {{ trans('messages.automation.outgoing_webhook.key') }}</th>
                                                <th width="50%" class="small" style="font-weight:normal;">
                                                    {{ trans('messages.automation.outgoing_webhook.value') }}</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody list-control="content">

                                        </tbody>
                                    </table>
                                    <div class="text-end mt-2">
                                        <button list-control="add" type="button" class="btn btn-primary btn-sm">
                                            <span class="d-flex align-items-center">
                                                <span class="material-symbols-rounded me-1">
                                                    add
                                                </span>
                                                <span>
                                                    {{ trans('messages.automation.outgoing_webhook.add_more_header') }}
                                                </span>
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div data-option-box="plain_text">
                                <label for=""
                                    class="form-label fw-semibold">{{ trans('messages.automation.outgoing_webhook.json_xml') }}</label>
                                <div id="editor" class="rounded shadow-sm" style="height: 200px;width: 100%;"></div>

                                <script src="/ace/src-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>
                                <textarea id="signatureContent" type="text" name="webhook[plain_text]" class="form-control required template-editor"
                                    style="display: none;">{!! $options['plain_text'] !!}</textarea>
                                <script>
                                    var editor = ace.edit("editor");
                                    // editor.setTheme("ace/theme/monokai");
                                    editor.getSession().setMode("ace/mode/json"); // Optional: set to HTML mode

                                    // Set the content, using Blade to escape it for raw HTML
                                    var content = ``;
                                    editor.setValue(content);

                                    // Set up a listener for changes
                                    editor.getSession().on('change', function(delta) {
                                        // This code will run every time the content changes
                                        var content = editor.getValue();
                                        document.getElementById('signatureContent').value = editor.getValue();
                                        // You can perform additional actions here, like saving or validating the content
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mt-3">
                        <button data-control="test-webhook" type="button" class="btn btn-info">
                            <span class="d-flex align-items-center">
                                <span class="material-symbols-rounded me-1">
                                    science
                                </span>
                                <span>
                                    {{ trans('messages.automation.outgoing_webhook.test_webhook') }}
                                </span>
                            </span>
                        </button>
                        <span class="mx-2">|</span>
                        <button type="button" data-control="save" class="btn btn-secondary me-2">{{ trans('messages.save') }}</button>
                        <a href="javascript:;" onclick="cartWait.hide()" class="btn btn-link me-2"
                            data-dismiss="modal">{{ trans('messages.close') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(() => {
            window.webhookForm = new WebhookForm({
                form: $('#{{ $formId }}'),
                testUrl: '{{ action('Automation2Controller@outgoingWebhookTestPopup', $automation->uid) }}',
            });
        });

        var WebhookForm = class {
            constructor(options) {
                this.form = options.form;
                this.url = options.form.attr('action');
                this.testPopup = new Popup({
                    url: options.testUrl,
                });

                // input with tag
                this.form.find('[data-control="input-with-tag"]').each(function() {
                    new InputWithTag($(this));
                });

                // AuthorizationOptionSelector
                this.authorizationOptionSelector = new AuthorizationOptionSelector({
                    container: this.form.find('[data-control="authorization-options"]'),
                });

                // HeaderSelector
                this.headerSelector = new HeaderSelector({
                    container: this.form.find('[data-control="headers"]'),
                });

                // BodyConfigurationSelector
                this.bodyConfigurationSelector = new BodyConfigurationSelector({
                    container: this.form.find('[data-control="body-config"]'),
                });

                // HeaderListManager
                this.headerListManager = new HeaderListManager({
                    container: this.form.find('[data-control="header-list-container"]'),
                    elements: {!! json_encode($options['headers']) !!},
                });

                // BodyParametersManager
                this.bodyParametersManager = new BodyParametersManager({
                    container: this.form.find('[data-control="body-parameters-container"]'),
                    elements: {!! json_encode($options['body_parameters']) !!},
                });

                //
                this.events();
            }

            getTestWebhookButton() {
                return this.form.find('[data-control="test-webhook"]');
            }

            getSaveButton() {
                return this.form.find('[data-control="save"]');
            }

            events() {
                // test webhook click
                this.getTestWebhookButton().on('click', () => {
                    this.testWebhook();
                });

                // save
                this.getSaveButton().on('click', () => {
                    this.save();
                });
            }

            testWebhook() {
                if (!this.form[0].reportValidity()) {
                    // alert("Form is valid!");
                    return;
                }
                
                this.testPopup.load();
            }

            getData() {
                return this.form.serialize();
            }

            save() {
                if (!this.form[0].reportValidity()) {
                    // alert("Form is valid!");
                    return;
                }

                $.ajax({
                    url: this.url,
                    type: 'POST',
                    data: this.form.serialize(),
                }).done((res) => {
                    @if ($element)
                        // merge options with reponse options
                        tree.getSelected().setOptions($.extend(tree.getSelected().getOptions(), res.options));
                        tree.getSelected().validate();
                            
                        // save tree
                        saveData(function() {
                            // hide popup
                            automationPopup.hide();
                            
                            //
                            notify({
                                type: 'success',
                                title: '{!! trans('messages.notify.success') !!}',
                                message: '{{ trans('messages.automation.outgoing_webhook.updated') }}'
                            });

                            //
                            sidebar.load();
                        });
                    @else
                        var newE = new ElementWebhook(res);
                        MyAutomation.addToTree(newE);

                        // save tree
                        saveData(function() {
                            // hide popup
                            automationPopup.hide();
                            
                            //
                            notify({
                                type: 'success',
                                title: '{!! trans('messages.notify.success') !!}',
                                message: '{{ trans('messages.automation.outgoing_webhook.added') }}'
                            });

                            // select newly added element
                            doSelectTreeElement(newE);
                        });
                    @endif
                });
            }
        };

        // Input with tag
        var InputWithTag = class {
            constructor(control) {
                this.control = control;

                this.events();
            }

            getTagSelectors() {
                return this.control.find('[tag-control="selector"]');
            }

            getInput() {
                return this.control.find('[tag-control="input"]');
            }

            events() {
                var _this = this;

                this.getTagSelectors().on('click', function (e) {
                    e.preventDefault();

                    var tag = $(this).attr('data-tag');
                    _this.insertTag(tag);
                })
            }

            insertTag(tag) {
                this.getInput().val(function(index, value) {
                    return value + tag;
                }).trigger('change');
            }
        }

        // BodyParametersManager
        var BodyParametersManager = class {
            constructor(options) {
                this.container = options.container;
                this.elements = options.elements;

                // init items
                if (!options.elements) {
                    this.elements = options.elements;
                }

                // first empty element if list is empty
                if (this.elements.length == 0) {
                    this.addElement({
                        key: '',
                        value: '',
                    });
                }

                // render
                this.render();

                // global events
                this.events()
            }

            getContent() {
                return this.container.find('[list-control="content"]');
            }

            getAddButton() {
                return this.container.find('[list-control="add"]');
            }

            addElement(element) {
                this.elements.push(element);
            }

            removeElement(index) {
                this.elements.splice(index, 1);
            }

            render() {
                this.getContent().html('');

                this.elements.forEach((element, index) => {
                    var e = $(`
                        <tr>
                            <td class="bg-light">
                                <input list-control="input-key" type="text" name="webhook[body_parameters][` + index +
                        `][key]" value="` + element.key + `" class="form-control" placeholder="{{ trans('messages.automation.outgoing_webhook.key') }}"
                                data-option-input="required"
                        />
                            </td>
                            <td class="bg-light">
                                <div data-control="input-with-tag" class="input-group">
                                    <input data-option-input="required" tag-control="input" list-control="input-value" type="text" name="webhook[body_parameters][` + index + `][value]"
                                        value="` + element.value + `" class="form-control"
                                        placeholder="{{ trans('messages.automation.outgoing_webhook.value') }}"
                                    />
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">...</button>
                                    <ul class="dropdown-menu">
                                        @foreach ($automation->getAvailableOutgoingWebhookTags() as $tag)
                                            <li>
                                                <a tag-control="selector" data-tag="{{ $tag['tag'] }}" class="dropdown-item border-bottom" href="#">
                                                    <span class="fw-semibold">{{ $tag['label'] }}</span><br>
                                                    <span>{{ $tag['tag'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>

                                </div>
                            </td>
                            <td width="1%" class="text-center" style="min-width: 52px;">
                                <button list-control="remove" type="button" class="btn btn-danger px-2">
                                    <span class="d-flex align-items-center">
                                        <span class="material-symbols-rounded">
                                            delete
                                        </span>
                                    </span>
                                </button>
                            </td>
                        </tr>
                    `);

                    this.getContent().append(e);

                    // element events
                    e.find('[list-control="remove"]').on('click', () => {
                        this.removeElement(index);

                        // 
                        this.render();
                    })

                    // update key
                    e.find('[list-control="input-key"]').on('change', () => {
                        this.elements[index].key = e.find('[list-control="input-key"]').val();
                    })

                    // update value
                    e.find('[list-control="input-value"]').on('change', () => {
                        this.elements[index].value = e.find('[list-control="input-value"]').val();
                    })

                    // input with tag
                    e.find('[data-control="input-with-tag"]').each(function() {
                        new InputWithTag($(this));
                    });
                });
            }

            events() {
                this.getAddButton().on('click', () => {
                    this.addElement({
                        key: '',
                        value: '',
                    });

                    //
                    this.render();
                })
            }
        }

        // HeaderListManager
        var HeaderListManager = class {
            constructor(options) {
                this.container = options.container;
                this.elements = options.elements;

                // init items
                if (!options.elements) {
                    this.elements = options.elements;
                }

                // first empty element if list is empty
                if (this.elements.length == 0) {
                    this.addElement({
                        key: '',
                        value: '',
                    });
                }

                // render
                this.render();

                // global events
                this.events()
            }

            getContent() {
                return this.container.find('[list-control="content"]');
            }

            getAddButton() {
                return this.container.find('[list-control="add"]');
            }

            addElement(element) {
                this.elements.push(element);
            }

            removeElement(index) {
                this.elements.splice(index, 1);
            }

            render() {
                this.getContent().html('');

                this.elements.forEach((element, index) => {
                    var e = $(`
                        <tr>
                            <td class="bg-light">
                                <input list-control="input-key" type="text" name="webhook[headers][` + index +
                        `][key]" value="` + element.key + `" class="form-control" placeholder="{{ trans('messages.automation.outgoing_webhook.header_key') }}"
                                 data-option-input="required"
                        />
                            </td>
                            <td class="bg-light">
                                <div data-control="input-with-tag" class="input-group">
                                    <input tag-control="input" list-control="input-value" type="text" name="webhook[headers][` + index + `][value]"
                                        value="` + element.value + `" class="form-control"
                                        placeholder="{{ trans('messages.automation.outgoing_webhook.header_value') }}"
                                         data-option-input="required"
                                    />
                                    <button class="btn btn-outline-secondary dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">...</button>
                                    <ul class="dropdown-menu">
                                        @foreach ($automation->getAvailableOutgoingWebhookTags() as $tag)
                                            <li>
                                                <a tag-control="selector" data-tag="{{ $tag['tag'] }}" class="dropdown-item border-bottom" href="#">
                                                    <span class="fw-semibold">{{ $tag['label'] }}</span><br>
                                                    <span>{{ $tag['tag'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>

                                </div>
                                
                            </td>
                            <td width="1%" class="text-center" style="min-width: 52px;">
                                <button list-control="remove" type="button" class="btn btn-danger px-2 ` + (index ==
                            0 ? 'd-none' : '') + `">
                                    <span class="d-flex align-items-center">
                                        <span class="material-symbols-rounded">
                                            delete
                                        </span>
                                    </span>
                                </button>
                            </td>
                        </tr>
                    `);

                    this.getContent().append(e);

                    // element events
                    e.find('[list-control="remove"]').on('click', () => {
                        this.removeElement(index);

                        // 
                        this.render();
                    })

                    // update key
                    e.find('[list-control="input-key"]').on('change', () => {
                        this.elements[index].key = e.find('[list-control="input-key"]').val();
                    })

                    // update value
                    e.find('[list-control="input-value"]').on('change', () => {
                        this.elements[index].value = e.find('[list-control="input-value"]').val();
                    })

                    // input with tag
                    e.find('[data-control="input-with-tag"]').each(function() {
                        new InputWithTag($(this));
                    });
                });
            }

            events() {
                this.getAddButton().on('click', () => {
                    this.addElement({
                        key: '',
                        value: '',
                    });

                    //
                    this.render();
                })
            }
        }

        // AuthorizationOptionSelector
        var AuthorizationOptionSelector = class {
            constructor(options) {
                this.container = options.container;

                // current checked
                if (this.getCheckedChecker()) {
                    this.select(this.getCheckedChecker().val());
                }

                this.events();
            }

            getCheckers() {
                return this.container.find('[data-control="method"]');
            }

            getCheckedChecker() {
                return this.getCheckers().filter(':checked');
            }

            getOptionBoxes() {
                return this.container.find('[data-option-box]');
            }

            getOptionBoxByValue(value) {
                return this.container.find('[data-option-box="' + value + '"]');
            }

            events() {
                var _this = this;

                this.getCheckers().on('change', function(e) {
                    var checker = $(this);
                    var value = checker.val();

                    // if checked
                    if (checker.is(':checked')) {
                        _this.select(value);
                    }
                });
            }

            select(value) {
                // hide all option boxes
                this.getOptionBoxes().hide();

                // required input
                this.getOptionBoxes().find('[data-option-input="required"]').prop('required', false);

                // show selected box
                this.getOptionBoxByValue(value).fadeIn();

                // required input
                this.getOptionBoxByValue(value).find('[data-option-input="required"]').prop('required', true);
            }
        };

        // HeaderSelector
        var HeaderSelector = class {
            constructor(options) {
                this.container = options.container;

                // current checked
                if (this.getCheckedChecker()) {
                    this.select(this.getCheckedChecker().val());
                }

                this.events();
            }

            getCheckers() {
                return this.container.find('[data-control="checker"]');
            }

            getCheckedChecker() {
                return this.getCheckers().filter(':checked');
            }

            getOptionBoxes() {
                return this.container.find('[data-option-box]');
            }

            getOptionBoxByValue(value) {
                return this.container.find('[data-option-box="' + value + '"]');
            }

            events() {
                var _this = this;

                this.getCheckers().on('change', function(e) {
                    var checker = $(this);
                    var value = checker.val();

                    // if checked
                    if (checker.is(':checked')) {
                        _this.select(value);
                    }
                });
            }

            select(value) {
                // hide all option boxes
                this.getOptionBoxes().hide();

                // required input
                this.getOptionBoxes().find('[data-option-input="required"]').prop('required', false);

                // show selected box
                this.getOptionBoxByValue(value).fadeIn();

                // required input
                this.getOptionBoxByValue(value).find('[data-option-input="required"]').prop('required', true);
            }
        };

        // BodyConfigurationSelector
        var BodyConfigurationSelector = class {
            constructor(options) {
                this.container = options.container;

                // current checked
                this.select(this.getSelector().val());

                this.events();
            }

            getSelector() {
                return this.container.find('[data-control="selector"]');
            }

            getOptionBoxes() {
                return this.container.find('[data-option-box]');
            }

            getOptionBoxByValue(value) {
                return this.container.find('[data-option-box="' + value + '"]');
            }

            events() {
                var _this = this;

                this.getSelector().on('change', function(e) {
                    var value = $(this).val();

                    // if checked
                    _this.select(value);
                });
            }

            select(value) {
                // hide all option boxes
                this.getOptionBoxes().hide();

                // required input
                this.getOptionBoxes().find('[data-option-input="required"]').prop('required', false);

                // show selected box
                this.getOptionBoxByValue(value).fadeIn();

                // required input
                this.getOptionBoxByValue(value).find('[data-option-input="required"]').prop('required', true);
            }
        };
    </script>
@endsection
