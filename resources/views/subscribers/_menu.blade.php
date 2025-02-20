@php $menu = $menu ?? 'profile' @endphp

<div class="row">
    <div class="col-md-12">
        <ul class="nav nav-tabs nav-tabs-top nav-underline mb-1">
            <li class="nav-item {{ in_array($menu, ['profile']) ? 'active' : '' }}">
                <a href="{{ action('SubscriberController@edit', [
                    'list_uid' => $subscriber->mailList->uid,
                    'id' => $subscriber->id,
                ]) }}" class="nav-link">
                    <span class="material-symbols-rounded">badge</span> {{ trans('messages.subscriber.profile') }}
                </a>
            </li>
            <li class="nav-item {{ in_array($menu, ['timeline']) ? 'active' : '' }}">
                <a href="{{ action('SubscriberController@timeline', [
                    'id' => $subscriber->id,
                ]) }}" class="nav-link">
                    <span class="material-symbols-rounded">history_toggle_off</span> {{ trans('messages.subscriber.activities') }}
                </a>
            </li>
        </ul>
    </div>
</div>
