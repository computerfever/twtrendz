@extends('layouts.popup.small')

@section('content')
    <div class="row">
        <div class="col-md-12">
            <form data-control="WaitActionForm" id="action-select" action="{{ action('Automation2Controller@waitSave', ['uid' => $automation->uid]) }}" method="POST" class="form-validate-jqueryz">
                {{ csrf_field() }}

                <input type="hidden" name="key" value="wait" />

                @include('automation2.action.wait')

                <button class="btn btn-secondary select-action-confirm mt-2">
                        {{ trans('messages.automation.trigger.select_confirm') }}
                </button>
            </form>
        </div>
    </div>

    <script>
        function selectActionSubmit(url, data) {
            // show loading effect
            automationPopup.loading();

            $.ajax({
                url: url,
                type: 'POST',
                data: data,
            }).always(function(response) {
                var newE = new ElementWait({title: response.title, options: response.options});

                MyAutomation.addToTree(newE);

                newE.validate();
                
                // save tree
                saveData(function() {
                    // hide popup
                    automationPopup.hide();

                    doSelectTreeElement(newE);
                    
                    notify({
                        type: 'success',
                        title: '{!! trans('messages.notify.success') !!}',
                        message: response.message
                    });
                });
            });
        }

        $(() => {
            $('[data-control="WaitActionForm"]').submit(function(e) {
                e.preventDefault();

                var url = $(this).attr('action');
                var data = $(this).serialize();
                
                // submit form
                selectActionSubmit(url, data);
            });
        })
    </script>
@endsection
