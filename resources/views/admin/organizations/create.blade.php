@extends('layouts.app')

@section('title', 'New Organization')

@section('content')
<div class="container-fluid" style="max-width: 860px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">New Organization</h1>
            <p class="text-muted mb-0">Create a new organization on this Voluntrack instance.</p>
        </div>
        <a href="{{ route('admin.organizations.index') }}" class="btn btn-outline-secondary btn-sm">← Organizations</a>
    </div>

    <form method="POST" action="{{ route('admin.organizations.store') }}" enctype="multipart/form-data">
        @csrf

        @include('admin.organizations._form', ['organization' => new \App\Models\Organization()])

        <div class="d-flex justify-content-end gap-2 pb-5">
            <a href="{{ route('admin.organizations.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Create Organization</button>
        </div>
    </form>

</div>
@endsection

@push('scripts')
<script>
(function () {
    // Auto-generate slug from name
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    let userEditedSlug = false;

    if (slugInput.value) userEditedSlug = true;

    nameInput?.addEventListener('input', () => {
        if (userEditedSlug) return;
        slugInput.value = nameInput.value
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    });
    slugInput?.addEventListener('input', () => { userEditedSlug = true; });

    // Colour picker sync
    const picker = document.getElementById('accent_color');
    const hex    = document.getElementById('accent_color_hex');
    picker?.addEventListener('input', () => { hex.value = picker.value; });
    hex?.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) picker.value = hex.value;
    });
})();
</script>
@endpush
