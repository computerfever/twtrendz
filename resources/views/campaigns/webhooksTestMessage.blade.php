@if (isset($result['error']))
    <div class="alert alert-danger">
        {{ $result['error'] }}
    </div>
@else
    @php
        $status = $result['code'] == 200 ? 'info' : 'danger';
    @endphp
    <div class="mb-4">
        <span class="badge rounded badge-lg py-1 badge-{{ $status }}">{{ $result['code'] }}</span>
        <span class="" style="display:none"><code class="text-primary">{{ $result['responseBody'] }}</code></span>
    </div>
@endif