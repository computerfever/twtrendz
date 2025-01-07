<script>

class PhoneFieldWidget extends FieldWidget {
    constructor(field) {
        super(field);

        var thisWidget = this;
        thisWidget.field = field;

        // update button html
        var html = thisWidget.getButtonHtml();
        html = thisWidget.replaceTag(html);
        thisWidget.setButtonHtml(html);

        // update content html
        var html = thisWidget.getContentHtml();
        html = thisWidget.replaceTag(html);
        thisWidget.setContentHtml(html);

        // update dragging html
        var html = thisWidget.getDraggingHtml();
        html = thisWidget.replaceTag(html);
        thisWidget.setDraggingHtml(html);
    }

    getHtmlId() {
        return "PhoneFieldWidget";
    }

    init() {
            var _this = this;

            // default button html
            this.setButtonHtml(`
                <div class="form-element-widget">
                    <span class="icon">
                        <i class="material-icons-outlined" style="font-size: 30px;display: inline-block;">text_format</i>
                    </span>
                    <span class="field-label mb-0"><span class="label">[FIELD_LABEL]</span><span class="small text-muted hide">{language.wpanel.widgets.text}</span></span>
                </div>
            `);

            // default content html
            var config = Base64.encode(JSON.stringify({!! json_encode(\Acelle\Model\Template::defaultRssConfig()) !!}));
            this.setContentHtml(`
                <div id="`+this.id+`" builder-element="TextFieldElement" class="form-group">
                    <div class="">
                        <label>
                            [FIELD_LABEL]
                        </label>
                        <div>
                            <input
                                [FIELD_REQUIRED]
                                id="[FIELD_NAME]" type="text" name="[FIELD_NAME]" class="form-control"
                                value="">
                        </div>
                    </div>
                </div>
            `);

            // default dragging html
            this.setDraggingHtml(this.getButtonHtml());
        }

        getPlaceholder() {
            return this.getElement().find('[f-role="placeholder"]');
        }

        getElement() {
            return currentEditor.getIframeContent().find('#' + this.id);
        }

        drop() {
            var element = currentEditor.elementFactory(this.getElement());

            currentEditor.select(element);
            currentEditor.handleSelect();

            // var phoneInputFieldHelper = $("#builder_iframe")[0].contentWindow.document.querySelector("#phone_"+this.field.name+"_helper");
            // var phoneInputField = $("#builder_iframe")[0].contentWindow.document.querySelector("#phone_"+this.field.name+"");
            // var phoneInput = $("#builder_iframe")[0].contentWindow.window.intlTelInput(phoneInputFieldHelper, {
            //     initialValue: '',
            //     utilsScript: "{{ AppUrl::asset('core/phoneinput/utils.js') }}",
            // });

            // $(function() {
            //     $(phoneInputField).closest('form').on('submit', function(e) {
            //         $(phoneInputField).val(phoneInput.getNumber());
            //     });
            // });
        }
}

</script>