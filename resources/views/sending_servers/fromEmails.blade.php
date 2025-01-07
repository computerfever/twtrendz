@extends('layouts.popup.small')

@section('content')
	<div class="row">
        <div class="col-md-12">
            <p class="mb-2">{{ trans('messages.below_are_from_emails', [
                'number' => count($sendingServers),
            ]) }}</p>

            <table class="table">
                @foreach ($sendingServers as $sendingServer)
                    <tr>
                        <td class="ps-0">{{ $sendingServer->name }}</td>
                        <td>
                            <a class="d-inline-block fst-italic" href="mailto:{{ $sendingServer->default_from_email }}" class="">
                                {{ $sendingServer->default_from_email ?? trans('messages.default_from_email.not_set') }}
                            </a>
                        </td>
                    </tr>
                @endforeach
            </table>

            <div class="mt-4">
                <button class="btn btn-secondary close">{{ trans('messages.close') }}</button>
            </div>
        </div>
    </div>
@endsection