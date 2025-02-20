@extends('layouts.popup.large')

@section('content')
    <style>
        @import url(https://fonts.googleapis.com/css?family=Roboto:100);

        #webhook-loading {
            display: inline-block;
            width: 50px;
            height: 50px;
            border: 3px solid #007c99;
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            -webkit-animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                -webkit-transform: rotate(360deg);
            }
        }

        @-webkit-keyframes spin {
            to {
                -webkit-transform: rotate(360deg);
            }
        }
    </style>

    <div test-control="result">
        <div class="my-4">
            <div class="d-flex align-items-center justify-content-center">
                <div id="webhook-loading"></div>
            </div>
            <div class="text-center mt-4 fs-5">
                {{ trans('messages.automation.outgoing_webhook.testing_webhook') }}
            </div>
        </div>
    </div>

    <script>
        $(() => {
            new WebhookTest({
                resultBox: $('[test-control="result"]'),
                url: '{{ action('Automation2Controller@outgoingWebhookTest', $automation->uid) }}',
            });
        });

        var WebhookTest = class {
            constructor(options) {
                this.resultBox = options.resultBox;
                this.url = options.url;

                this.test();
            }

            test() {
                $.ajax({
                    url: this.url,
                    type: 'POST',
                    data: window.webhookForm.getData(),
                }).done((response) => {
                    this.resultBox.html(response);               
                });
            }
        }
    </script>
@endsection
