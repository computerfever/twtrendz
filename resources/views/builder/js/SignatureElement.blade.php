<script>
    // SignatureElement
    class SignatureElement extends SuperElement  {
        name() {
            var element = this; 
            return getI18n('block');
        }

        getControls() {
            var element = this;

            return [
                new FontFamilyControl(getI18n('font_family'), element.obj.css('font-family'), function(font_family) {
                    element.obj.css('font-family', font_family);
                    element.select();
                }),
                new BackgroundImageControl(getI18n('background_image'), {
                    image: element.obj.css('background-image'),
                    color: element.obj.css('background-color'),
                    repeat: element.obj.css('background-repeat'),
                    position: element.obj.css('background-position'),
                    size: element.obj.css('background-size'),
                }, {
                    setBackgroundImage: function (image) {
                        element.obj.css('background-image', image);
                    },
                    setBackgroundColor: function (color) {
                        element.obj.css('background-color', color);
                    },
                    setBackgroundRepeat: function (repeat) {
                        element.obj.css('background-repeat', repeat);
                    },
                    setBackgroundPosition: function (position) {
                        element.obj.css('background-position', position);
                    },
                    setBackgroundSize: function (size) {
                        element.obj.css('background-size', size);
                    },
                }),
                new BlockOptionControl(getI18n('block_options'), { padding: element.obj.css('padding'), top: element.obj.css('padding-top'), bottom: element.obj.css('padding-bottom'), right: element.obj.css('padding-right'), left: element.obj.css('padding-left') }, function(options) {
                    element.obj.css('padding', options.padding);
                    element.obj.css('padding-top', options.top);
                    element.obj.css('padding-bottom', options.bottom);
                    element.obj.css('padding-right', options.right);
                    element.obj.css('padding-left', options.left);
                    element.select();
                })
            ];
        }
    }
</script>