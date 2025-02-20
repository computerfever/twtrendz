
@switch($timeline->type)
    @case(\Acelle\Model\Timeline::TYPE_ADDED_BY_CUSTOMER)
        {!! trans('messages.timeline.message.added_by_customer', [
            'list' => $timeline->subscriber->mailList->name,
            'customer' => $timeline->customer->displayName(),
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_SIGN_UP_FORM_OPT_IN)
        {!! trans('messages.timeline.message.sign_up_form_opt_in', [
            'list' => $timeline->subscriber->mailList->name,
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_EMBEDDED_FORM_OPT_IN)
        {!! trans('messages.timeline.message.embedded_form_opt_in', [
            'list' => $timeline->subscriber->mailList->name,
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_POPUP_FORM_OPT_IN)
        @php
            $formName = $timeline->form ? $timeline->form->name : trans('messages.general.n_a');
        @endphp
        {!! trans('messages.timeline.message.popup_form_opt_in', [
            'list' => $timeline->subscriber->mailList->name,
            'form' => $formName,
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_UNSUBSCRIBED_BY_CUSTOMER)
        {!! trans('messages.timeline.message.unsubscribed_by_customer', [
            'list' => $timeline->subscriber->mailList->name,
            'customer' => $timeline->customer->displayName(),
        ]) !!}
    @break
        @case(\Acelle\Model\Timeline::TYPE_SUBSCRIBED_BY_CUSTOMER)
        {!! trans('messages.timeline.message.subscribed_by_customer', [
            'list' => $timeline->subscriber->mailList->name,
            'customer' => $timeline->customer->displayName(),
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_API_ADDED)
        {!! trans('messages.timeline.message.api_added', [
            'list' => $timeline->subscriber->mailList->name,
            'customer' => $timeline->customer->displayName(),
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_API_SUBSCRIBED)
        {!! trans('messages.timeline.message.api_subscribed', [
            'list' => $timeline->subscriber->mailList->name,
            'customer' => $timeline->customer->displayName(),
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_COPIED_TO)
        {!! trans('messages.timeline.message.copied_to', [
            'list' => $timeline->mailList->name,
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_COPIED_FROM)
        {!! trans('messages.timeline.message.copied_from', [
            'list' => $timeline->mailList->name,
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_MOVED_FROM)
        {!! trans('messages.timeline.message.moved_from', [
            'list' => $timeline->mailList->name,
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_UNSUBSCRIBED_FROM_LIST_UNSUBSCRIBE_FORM)
        {!! trans('messages.timeline.message.unsubscribed_from_list_unsubscribe_form', [
            'list' => $timeline->subscriber->mailList->name,
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_OPENED_CAMPAIGN_EMAIL)
        @php
            $campaignName = $timeline->campaign ? $timeline->campaign->name : trans('messages.general.n_a');
        @endphp
        {!! trans('messages.timeline.message.opened_campaign_email', [
            'subscriber' => $timeline->subscriber->email,
            'campaign_name' => $campaignName,
        ]) !!}
        @break
    @case(\Acelle\Model\Timeline::TYPE_CLICKED_CAMPAIGN_EMAIL)
        @php
            $campaignName = $timeline->campaign ? $timeline->campaign->name : trans('messages.general.n_a');
        @endphp
        {!! trans('messages.timeline.message.clicked_campaign_email', [
            'subscriber' => $timeline->subscriber->email,
            'campaign_name' => $campaignName,
        ]) !!}
        @break
    @default
        
@endswitch