@extends('layouts.admin')

@section('title', 'Edit Organization')

@section('content')
<div class="container-fluid" style="max-width: 860px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Edit: {{ $organization->name }}</h1>
            <p class="text-muted mb-0">
                Public URL:
                <a href="{{ route('org.hours.index', $organization) }}" target="_blank" class="font-monospace small">
                    /{{ $organization->slug }} ↗
                </a>
            </p>
        </div>
        <a href="{{ route('admin.organizations.index') }}" class="btn btn-outline-secondary btn-sm">← Organizations</a>
    </div>

    <form method="POST" action="{{ route('admin.organizations.update', $organization) }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('admin.organizations._form')

        <div class="d-flex justify-content-end gap-2 pb-5">
            <a href="{{ route('admin.organizations.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
(function () {
    const picker = document.getElementById('accent_color');
    const hex    = document.getElementById('accent_color_hex');
    picker?.addEventListener('input', () => { hex.value = picker.value; });
    hex?.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) picker.value = hex.value;
    });
})();
</script>
@endpush
