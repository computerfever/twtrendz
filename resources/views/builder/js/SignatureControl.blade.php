<script>
    // SignatureControl
    class SignatureControl extends Control {
        renderHtml() {
            var thisControl = this;
            var html = `
                <div id="SignatureControl">
                    <div class="control-[ID]">
                        adasd
                        
                    </div>
                </div>
            `;
            thisControl.selector = ".control-" + thisControl.id;

            html = html.replace("[ID]", thisControl.id);

            var div = $('<DIV>').html(html);
            
            return div.html();
        }

        afterRender() {
            var thisControl = this;
        }
    }
</script>