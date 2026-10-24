@extends('layouts.admin')

@section('title', 'New Organization')

@section('content')
<div class="container-fluid" style="max-width: 860px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">New Organization</h1>
            <p class="text-muted mb-0">Start with a name and a public link. Invite your team and personalize the form next.</p>
        </div>
        <a href="{{ route('admin.organizations.index') }}" class="btn btn-outline-secondary btn-sm">← Organizations</a>
    </div>

    <form method="POST" action="{{ route('admin.organizations.store') }}" enctype="multipart/form-data">
        @csrf

        <input type="hidden" name="is_active" value="1">
        <div class="card mb-4"><div class="card-body p-4">
            <p class="eyebrow">Step 1 / A home for your organization</p>
            <div class="mb-4"><label for="name" class="form-label">Organization name</label><input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="QC NEMBA" required maxlength="255">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-4"><label for="slug" class="form-label">Public URL slug</label><div class="input-group"><span class="input-group-text">/</span><input id="slug" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug') }}" required maxlength="100" pattern="[a-z0-9]+((_|-)[a-z0-9]+)*" aria-describedby="slug-help"><span class="input-group-text">/log-hours</span></div><div class="form-text" id="slug-help">Generated from the name. Customize it to something memorable, such as qcnemba.</div>@error('slug')<p class="text-danger small">{{ $message }}</p>@enderror</div>
            <div class="row g-3">
                <div class="col-sm-4"><label for="accent_color" class="form-label">Accent color</label><input type="color" id="accent_color" name="accent_color" class="form-control form-control-color" value="{{ old('accent_color', '#0d6efd') }}">@error('accent_color')<p class="text-danger small">{{ $message }}</p>@enderror</div>
                <div class="col-sm-8"><label for="logo" class="form-label">Logo <span class="text-muted">(optional)</span></label><input type="file" id="logo" name="logo" class="form-control" accept="image/jpeg,image/png,image/webp"><div class="form-text">JPG, PNG, or WebP, up to 2 MB.</div>@error('logo')<p class="text-danger small">{{ $message }}</p>@enderror</div>
            </div>
        </div></div>

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
    picker?.addEventListener('input', () => { if (hex) hex.value = picker.value; });
    hex?.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) picker.value = hex.value;
    });
})();
</script>
@endpush
