@extends('layouts.core.frontend', [
	'menu' => 'subscriber',
])

@section('title', $list->name . ": " . trans('messages.create_subscriber'))

@section('head')
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/anytime.min.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/pickadate/picker.js') }}"></script>
    <script type="text/javascript" src="{{ AppUrl::asset('core/datetime/pickadate/picker.date.js') }}"></script>
@endsection

@section('page_header')

    @include("lists._header")

@endsection

@section('content')
    @include("lists._menu", [
		'menu' => 'subscriber',
	])
    
    @include("subscribers._header")

    <div class="row">
        <div class="col-sm-12 col-md-6 col-lg-6">
            @include("subscribers._menu", [
                'menu' => 'profile',
            ])

            <form enctype="multipart/form-data"  action="{{ action('SubscriberController@update', ['list_uid' => $list->uid, "id" => $subscriber->id]) }}" method="POST" class="form-validate-jqueryz">
                {{ csrf_field() }}
                <input type="hidden" name="_method" value="PATCH">
                <input type="hidden" name="list_uid" value="{{ $list->uid }}" />
                <div class="sub-section mt-4">
                    <div class="d-flex align-items-top">
                        <div>
                            @include('helpers._upload',[
                                'src' => (isSiteDemo() ? 'https://i.pravatar.cc/300' : action('SubscriberController@avatar',  $subscriber->id)),
                                'srcOrigin' => (isSiteDemo() ? 'https://i.pravatar.cc/300' : action('SubscriberController@avatarOrigin',  $subscriber->id)),
                                'dragId' => 'upload-avatar',
                                'preview' => 'image'
                            ])
                        </div>
                        <div class="mt-20">
                            <div class="dropdown">
                            <button class="btn btn-default bg-grey dropdown-toggle" role="button" id="dropdownMenu1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
                                {{ trans('messages.subscribers.profile.action') }}
                                <span class="caret"></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenu1">
                                <li><a class="dropdown-item profile-remove-contact" href="#">{{ trans('messages.subscribers.profile.remove_subscriber') }}</a></li>
                                <li><a class="dropdown-item profile-tag-contact" href="#">{{ trans('messages.subscribers.profile.manage_tags') }}</a></li>
                            </ul>
                            </div>
                        </div>
                    </div>
                    
                    <h3 class="clear-both">{{trans("messages.basic_information")}}</h3>
                    @include("subscribers._form")

                    <button class="btn btn-secondary me-2"><i class="icon-check"></i> {{ trans('messages.save') }}</button>
                    <a href="{{ action('SubscriberController@index', $list->uid) }}" class="btn btn-link"><i class="icon-cross2"></i> {{ trans('messages.cancel') }}</a>

                
                </div>
            </form>

            <div class="sub-section">
                <h3 class="text-semibold">{{ trans('messages.verification.title.email_verification') }}</h3>

                @if (is_null($subscriber->verification_status))
                    <p>{!! trans('messages.verification.wording.verify', [ 'email' => sprintf("<strong>%s</strong>", $subscriber->email) ]) !!}</p>
                    <form enctype="multipart/form-data" action="{{ action('SubscriberController@startVerification', ['id' => $subscriber->id]) }}" method="POST" class="form-validate-jquery">
                        {{ csrf_field() }}

                        <input type="hidden" name="list_uid" value="{{ $list->uid }}" />

                        @include('helpers.form_control', [
                            'type' => 'select',
                            'name' => 'email_verification_server_id',
                            'value' => '',
                            'options' => \Auth::user()->customer->emailVerificationServerSelectOptions(),
                            'help_class' => 'verification',
                            'rules' => ['email_verification_server_id' => 'required'],
                            'include_blank' => trans('messages.select_email_verification_server')
                        ])
                        <div class="text-left">
                            <button class="btn btn-secondary me-2"> {{ trans('messages.verification.button.verify') }}</button>
                        </div>
                    </form>
                @elseif ($subscriber->isDeliverable())
                    <p>{!! trans('messages.verification.wording.deliverable', [ 'email' => sprintf("<strong>%s</strong>", $subscriber->email), 'at' => sprintf("<strong>%s</strong>", $subscriber->last_verification_at) ]) !!}</p>
                    <form enctype="multipart/form-data" action="{{ action('SubscriberController@resetVerification', ['id' => $subscriber->id]) }}" method="POST" class="form-validate-jquery">
                        {{ csrf_field() }}
                        <input type="hidden" name="list_uid" value="{{ $list->uid }}" />

                        <div class="text-left">
                            <button class="btn btn-secondary me-2">{{ trans('messages.verification.button.reset') }}</button>
                        </div>
                    </form>
                @elseif ($subscriber->isUndeliverable())
                    <p>{!! trans('messages.verification.wording.undeliverable', [ 'email' => sprintf("<strong>%s</strong>", $subscriber->email)]) !!}</p>
                    <form enctype="multipart/form-data" action="{{ action('SubscriberController@resetVerification', ['id' => $subscriber->id]) }}" method="POST" class="form-validate-jquery">
                        {{ csrf_field() }}
                        <input type="hidden" name="list_uid" value="{{ $list->uid }}" />

                        <div class="text-left">
                            <button class="btn btn-secondary me-2">{{ trans('messages.verification.button.reset') }}</button>
                        </div>
                    </form>
                @else
                    <p>{!! trans('messages.verification.wording.risky_or_unknown', [ 'email' => sprintf("<strong>%s</strong>", $subscriber->email), 'at' => sprintf("<strong>%s</strong>", $subscriber->last_verification_at), 'result' => sprintf("<strong>%s</strong>", $subscriber->verification_status)]) !!}</p>
                    <form enctype="multipart/form-data" action="{{ action('SubscriberController@resetVerification', ['id' => $subscriber->id]) }}" method="POST" class="form-validate-jquery">
                        {{ csrf_field() }}
                        <input type="hidden" name="list_uid" value="{{ $list->uid }}" />

                        <div class="text-left">
                            <button class="btn btn-secondary me-2">{{ trans('messages.verification.button.reset') }}</button>
                        </div>
                    </form>
                @endif
            </div>
            
        </div>
    </div>

    

    <script>
        var tagContact = new Popup();
        $('.profile-tag-contact').click(function(e) {
            e.preventDefault();

            var url = '{{ action('SubscriberController@updateTags', [
                'list_uid' => $subscriber->mailList->uid,
                'id' => $subscriber->id,
            ]) }}';

            tagContact.load(url, function() {
				console.log('Confirm action type popup loaded!');				
			});
        });

        $('.remove-contact-tag').click(function(e) {
            e.preventDefault();

            var url = $(this).attr('href');

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: CSRF_TOKEN,
                },
                statusCode: {
                    // validate error
                    400: function (res) {
                        alert('Something went wrong!');
                    }
                },
                success: function (response) {
                    // notify
                    notify({
    type: 'success',
    title: '{!! trans('messages.notify.success') !!}',
    message: response.message
});

                    location.reload();
                }
            });
        });

        $('.profile-remove-contact').click(function(e) {
            e.preventDefault();

            var confirm = '{{ trans('messages.subscriber.delete.confirm') }}';
            var url = '{{ action('SubscriberController@delete', [
                'list_uid' => $subscriber->mailList->uid,
                'ids' => $subscriber->id,
            ]) }}';

            var dialog = new Dialog('confirm', {
                message: confirm,
                ok: function(dialog) {                    
                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            _token: CSRF_TOKEN,
                        },
                        statusCode: {
                            // validate error
                            400: function (res) {
                                alert('Something went wrong!');
                            }
                        },
                        success: function (response) {
                            // notify
                            notify({
    type: 'success',
    title: '{!! trans('messages.notify.success') !!}',
    message: response.message
});

                            // redirect
                            addMaskLoading('{{ trans('messages.subscriber.deleted.redirect') }}', function() {
                                window.location = '{{ action('SubscriberController@index', [
                                    'list_uid' => $subscriber->mailList->uid
                                ]) }}';
                            });
                        }
                    });
                },
            });
        });
    </script>
@endsection
