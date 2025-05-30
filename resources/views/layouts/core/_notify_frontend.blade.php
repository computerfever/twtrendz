<script>
    @if (null !== Session::get('orig_admin_id') && Auth::user()->admin)
        notify({
            type: 'warning',
            message: `{!! trans('messages.current_login_as', ["name" => Auth::user()->customer->displayName()]) !!}<br>{!! trans('messages.click_to_return_to_origin_user', ["link" => action("Admin\AdminController@loginBack")]) !!}`,
            timeout: false,
        });
    @endif

    @if (
        \Auth::user()->customer &&
        config('app.saas') &&
        \Auth::user()->customer->getCurrentActiveGeneralSubscription() &&
        \Auth::user()->customer->getCurrentActiveGeneralSubscription()->planGeneral->useOwnSendingServer() &&
        !\Auth::user()->customer->activeSendingServers()->count()
    )
        notify({
            type: 'warning',
            message: `{!! trans('messages.not_have_any_customer_sending_server', [
                'link' => action('SendingServerController@select'),
            ]) !!}`,
            timeout: false,
        });
    @endif

    @if(empty(Auth::user()->customer->contact->first_name) or empty(Auth::user()->customer->contact->last_name) or empty(Auth::user()->customer->contact->company) or empty(Auth::user()->customer->contact->email) or empty(Auth::user()->customer->contact->address_1) or empty(Auth::user()->customer->contact->country_id) or empty(Auth::user()->customer->contact->url))
        notify({
            type: 'warning',
            message:  `{!! trans('messages.complete_profile', [
                'link' => action('AccountController@contact'),
            ]) !!}`,
            timeout: false,
        });
    @endif

    @if (\Auth::user()->customer &&
        config('app.saas') &&
        !\Auth::user()->customer->getCurrentActiveGeneralSubscription() &&
        !isset($subscriptionPage)
    )
        notify({
            type: 'warning',
            message: `{!! trans('messages.not_have_any_plan_notification', [
                'link' => action('SubscriptionController@index'),
            ]) !!}`,
            timeout: false,
        });
    @endif
</script>