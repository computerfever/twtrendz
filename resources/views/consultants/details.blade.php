@extends('layouts.core.frontend')

@section('title', trans('Consultant'))

@section('content')
<div class="container">
    <h2 class="mt-4 pt-2">Consultant Details</h2>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('consultant.details.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div class="form-group mb-3">
            <label for="consultant_id">Consultant ID</label>
            <input type="text" class="form-control" id="consultant_id" minlength="11" maxlength="11" pattern="[0-9]+" name="consultant_id" placeholder="Enter your 11 digit Consultant ID " value="{{ $consultantDetails->consultant_id ?? '' }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="company_name">Company name</label>
            <input type="text" class="form-control" id="company_name" name="company_name" value="{{ $consultantDetails->company_name ?? '' }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="consultant_website">Consultant Website</label>
            <input type="text" class="form-control" id="consultant_website" name="consultant_website" placeholder="https://my.tupperware.com/" value="{{ $consultantDetails->consultant_website ?? '' }}" required>
        </div>

        <div class="form-group mb-3">
            <label for="consultant_message">Consultant Message</label>
            <textarea class="form-control" id="consultant_message" name="consultant_message" rows="4" required>{{ $consultantDetails->consultant_message ?? '' }}</textarea>
        </div>

        <div class="form-group mb-3">
            <label for="consultant_image">Profile Image (This feature is under development)</label>
            <input type="file" class="form-control" id="consultant_image" name="consultant_image" accept="image/*">
        </div>

        @if ($consultantDetails && $consultantDetails->consultant_image)
            <div class="mb-3">
                <img src="data:image/jpeg;base64,{{ base64_encode($consultantDetails->consultant_image) }}" alt="Profile Image" style="width: 150px; height: auto;">
            </div>
        @endif

        <button type="submit" class="btn btn-primary">Save</button>
    </form>
</div>

<script>
    document.getElementById('consultant_website').addEventListener('input', function() {
        const baseUrl = 'https://my.tupperware.com/';
        let inputValue = this.value;

        // Check if the input value already starts with the base URL
        if (!inputValue.startsWith(baseUrl)) {
            this.value = baseUrl + inputValue.replace(baseUrl, '');
        }
    });
</script>
@endsection
