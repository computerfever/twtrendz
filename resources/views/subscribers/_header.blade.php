<h2 class="font-weight-semibold mt-4">{{ $subscriber->getFullName() }}</h2>
<div class="tags mb-4" style="clear:both">
    <span class="font-weight-semibold mr-3">{{ trans('messages.tags') }}:</span>
    @if ($subscriber->getTags())
        @foreach ($subscriber->getTags() as $tag)
            <a href="{{ action('SubscriberController@removeTag', [
                'list_uid' => $subscriber->mailList->uid,
                'id' => $subscriber->id,
                'tag' => $tag,
            ]) }}" class="btn-group remove-contact-tag" role="group" aria-label="Basic example">
                <button role="button" class="btn btn-light btn-tag font-weight-semibold">{{ $tag }}</button>
                <button role="button" class="btn btn-light btn-tag font-weight-semibold ml-0">
                    <i class="material-symbols-rounded">close</i>
                </button>
            </a>
        @endforeach
    @else
        <a href="" class="btn-group profile-tag-contact" role="group" aria-label="Basic example">
            <button role="button" class="btn btn-light btn-tag d-flex align-items-center">
                <i class="material-symbols-rounded me-2">add</i>
                <span class="font-italic">{{ trans('messages.automation.profile.click_to_add_tag') }}<span>
            </button>
        </a>
    @endif
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
</script>