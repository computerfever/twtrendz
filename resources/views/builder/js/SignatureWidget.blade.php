<script>
    // Signature Widget
    class SignatureWidget extends Widget {
        getHtmlId() {
            return "SignatureWidget";
        }

        init() {
            var _this = this;

            // default button html
            this.setButtonHtml(`
                <div class="_1content widget-text">
                    <div class="panel__body woo-panel__body" title="{{ trans('builder.widget.signature') }}">
                        <div class="image-drag">
                            <div ng-bind-html="::getModuleIcon(module)" class="ng-binding product-list-widget">
                                <img builder-element style="width:50px;opacity:0.5" src="{{ url('images/signature_widget.svg') }}" width="100%" />
                            </div>
                        </div>
                        <div class="body__title">{{ trans('builder.widget.signature') }}</div>
                    </div>
                </div>
            `);

            // default content html
            this.setContentHtml(`
                <div id="`+this.id+`"
                    builder-element="SignatureElement"
                >
                    `+window.SingatureContent+`
                </div>
            `);

            // default dragging html
            this.setDraggingHtml(this.getButtonHtml());

            // // before save events: remove placeholder before save
            // currentEditor.addBeforeSaveEvent(function() {
            //     // find placeholder
            //     if (!_this.getPlaceholder().length) {
            //         return;
            //     }

            //     // find closest block element
            //     var blockElement = _this.getPlaceholder().closest('[builder-element="BlockElement"]');
            //     blockElement.remove();
            // });
        }

        getPlaceholder() {
            return this.getElement().find('[f-role="placeholder"]');
        }

        getElement() {
            return currentEditor.getIframeContent().find('#' + this.id);
        }

        drop() {
            // var element = currentEditor.elementFactory(this.getElement());

            // currentEditor.select(element);
            // currentEditor.handleSelect();

            // element.render();
        }
    }
</script>