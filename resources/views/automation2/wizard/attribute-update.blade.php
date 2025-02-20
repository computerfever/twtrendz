<div class="mb-4">
    <input type="hidden" name="options[type]" value="{{ Acelle\Model\Automation2::TRIGGER_REMOVE_TAG }}" />

    <div class="row">
        <div class="col-md-6 mt-2">
            @include('helpers.form_control', [
                'name' => 'mail_list_uid',
                'include_blank' => trans('messages.automation.choose_list'),
                'type' => 'select',
                'label' => trans('messages.list'),
                'value' => '',
                'options' => Auth::user()->customer->local()->readCache('MailListSelectOptions', []),
                'required' => true,
            ])

            <div class="automation-segment">

            </div>
        </div>
    </div>

    <div data-control="field-value" class="row" style="display: none;">
        <p class="mb-1">{{ trans('messages.automation.trigger.tree.attribute-update.select_field_value') }}</p>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="" class="form-label">{{ trans('messages.field') }}</label>

                    <select name="options[field_uid]" data-placeholder="{{ trans('messages.subscriber.choose_a_field') }}"
                        class="select select-search required" required
                    ></select>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="" class="form-label">{{ trans('messages.value') }}</label>

                    <input type="text" name="options[value]" required class="form-control" />
                </div>
            </div>
        </div>
    </div>
        
</div>

<script>
    $(() => {
        new FieldSelector({
            url: '{{ action('MailListController@listFieldOptionsUid', [
                'uid' => 'LIST_UID',
            ]) }}',
            container: $('[data-control="field-value"]'),
            listSelector: $('[name="mail_list_uid"]'),
            fieldSelector: $('[name="options[field_uid]"]'),
        });
    });

    var FieldSelector = class {
        constructor(options) {
            this.url = options.url;
            this.container = options.container;
            this.listSelector = options.listSelector;
            this.fieldSelector = options.fieldSelector;

            this.events();
        }

        events() {
            this.listSelector.on('change', () => {
                this.getOptions();
            });
        }

        getListUid() {
            return this.listSelector.val();
        }

        getOptions() {
            var $select = this.fieldSelector;

            // Clear existing options
            $select.empty();

            if (!this.getListUid()) {
                this.container.hide();
                return;
            }

            var url = this.url.replace('LIST_UID', this.getListUid());

            $.ajax({
                url: url,
                type: 'GET',
            }).done((options) => {
                // Add new options
                options.forEach(function(option) {
                    var newOption = new Option(option.text, option.uid, false, false);
                    $select.append(newOption);
                });

                // Refresh or reinitialize Select2
                $select.trigger('change'); // or $select.select2();

                this.container.show();
            });
        }
    }
</script>
