@extends('layouts.popup.small')

@section('title')
<span class="material-symbols-rounded me-1 text-muted2">content_copy</span> {!! trans('messages.segment.copy', [
        'name' => $segment->name
    ]) !!}
@endsection

@section('content')
    <form id="copySegmentForm"
        action="{{ action('SegmentController@copy', ['uid' => $segment->uid]) }}"
        method="POST">
        {{ csrf_field() }}          
        
        <p class="mb-2">{{ trans('messages.what_would_you_like_to_name_your_list') }}</p>

        <div class="mb-4">
            <input type="text" name="name" class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ (request()->has('name') ? request()->name : trans("messages.copy_of_list", ['name' => $segment->name])) }}" name="name">
            @if ($errors->has('name')) 
                <div class="invalid-feedback"> {{ $errors->first('name') }} </div>
            @endif
        </div>

        <div class="mt-4 text-center">
            <button id="copySegmentButton" type="submit" class="btn btn-secondary px-3 me-2">{{ trans('messages.copy') }}</button>
            <button type="button" class="btn btn-link fw-600" data-bs-dismiss="modal">{{ trans('messages.cancel') }}</button>
        </div>
    </form>


    <script>
        var SegmentsCopy = {
            copy: function(url, data) {
                window.copySegment.popup.mask();
                addButtonMask($('#copySegmentButton'));

                // copy
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: data,
                    globalError: false
                }).done(function(response) {
                    notify({
                        type: 'success',
                        message: response,
                    });

                    window.copySegment.popup.hide();
                    SegmentsIndex.getList().load();

                }).fail(function(jqXHR, textStatus, errorThrown){
                    // for debugging
                    window.copySegment.popup.loadHtml(jqXHR.responseText);
                }).always(function() {
                    window.copySegment.popup.unmask();
                    removeButtonMask($('#copySegmentButton'));
                });
            }
        }

        $(document).ready(function() {
            $('#copySegmentForm').on('submit', function(e) {
                e.preventDefault();
                var url = $(this).attr('action');
                var data = $(this).serialize();

                SegmentsCopy.copy(url, data);
            });
        });
    </script>
@endsection