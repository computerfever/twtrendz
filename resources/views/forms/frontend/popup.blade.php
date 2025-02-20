window.onload = function() {
    var popup = new AFormPopup({
        url: '{{ action('FormController@frontendContent', [
            'uid' => $form->uid,
        ]) }}',
        overlayOpacity: '{{ $form->getMetadata('overlay_opacity') ? ($form->getMetadata('overlay_opacity')/100) : '0.2' }}'
    });

    popup.init();
    
    @if ($form->getMetadata('display') == 'click')
        if (document.getElementById('{{ $form->getMetadata('element_id') }}')) {
            document.getElementById('{{ $form->getMetadata('element_id') }}').addEventListener("click", function(event) {
                popup.show();
            });
        }
        if (document.querySelectorAll('{!! $form->getMetadata('element_id') !!}').length) {
            document.querySelectorAll('{!! $form->getMetadata('element_id') !!}').forEach(ele => {
                ele.addEventListener("click", function(event) {
                    popup.show();
                });
            });
        }
    @elseif ($form->getMetadata('display') == 'wait')
        setTimeout(function() {
            popup.show();
        }, {{ $form->getMetadata('wait_time')*1000 }});

    @elseif ($form->getMetadata('display') == 'first_visit')
        popup.loadOneTime();
    @elseif ($form->getMetadata('display') == 'on_exit_intent')
        document.addEventListener('mouseleave', function(event) {
            if (event.clientY < 0) {
                showPopup();
            }
        });

        function showPopup() {
            popup.show();
        }

        function closePopup() {
            popup.hide();
        }

        window.addEventListener('beforeunload', function (event) {
            event.preventDefault();
            event.returnValue = '';
        });
    @else
        setTimeout(function() {
            popup.show();
        }, 500);
    @endif
};