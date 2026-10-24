@extends('layouts.admin')

@section('title', 'Branding Settings')

@section('content')
<div class="container-fluid" style="max-width: 860px;">

    {{-- Page Header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Branding &amp; Settings</h1>
            <p class="text-muted mb-0">Customize how <strong>{{ $org->name }}</strong> appears to volunteers.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('org.hours.index', $org) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                Preview Form ↗
            </a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
        </div>
    </div>

    <section class="share-panel mb-4">
        <p class="eyebrow">Ready for your community</p><h2 class="h5">Your public logging link</h2><p class="text-muted small">Save your changes, then preview the form exactly as volunteers will see it.</p>
        <label for="public-url" class="visually-hidden">Public logging URL</label><div class="d-flex flex-wrap gap-2"><input id="public-url" class="form-control share-url" readonly value="{{ route('org.hours.index', $org) }}"><button type="button" class="btn btn-primary" data-copy-target="public-url">Copy link</button></div><p class="small mt-2 mb-0" data-copy-status role="status"></p>
    </section>
    <form
        method="POST"
        action="{{ route('admin.branding.update') }}"
        enctype="multipart/form-data"
    >
        @csrf
        @method('PUT')

        {{-- ----------------------------------------------------------------- --}}
        {{-- Section 1: Organization Identity --}}
        {{-- ----------------------------------------------------------------- --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">Organization Identity</div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- Name --}}
                    <div class="col-12 col-md-8">
                        <label for="name" class="form-label">Organization Name</label>
                        <input
                            type="text"
                            name="name"
                            id="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $org->name) }}"
                            required
                            maxlength="255"
                        >
                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    {{-- Accent Colour --}}
                    <div class="col-12 col-md-4">
                        <label for="accent_color" class="form-label">Accent Colour</label>
                        <div class="input-group">
                            <input
                                type="color"
                                name="accent_color"
                                id="accent_color"
                                class="form-control form-control-color @error('accent_color') is-invalid @enderror"
                                value="{{ old('accent_color', $org->accent_color) }}"
                                title="Choose your brand colour"
                            >
                            <input
                                type="text"
                                id="accent_color_hex"
                                class="form-control font-monospace"
                                value="{{ old('accent_color', $org->accent_color) }}"
                                maxlength="7"
                                aria-label="Hex colour value"
                            >
                        </div>
                        <div class="form-text">Used for buttons and links.</div>
                        @error('accent_color') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    {{-- Logo --}}
                    <div class="col-12">
                        <label class="form-label">Logo</label>
                        @if($org->logo_url)
                            <div class="mb-2 d-flex align-items-center gap-3">
                                <img src="{{ $org->logo_url }}" alt="Current logo"
                                     style="height:48px;width:auto;object-fit:contain;background:#f8f9fa;padding:4px;border-radius:4px;border:1px solid #dee2e6;">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1">
                                    <label class="form-check-label text-danger small" for="remove_logo">Remove current logo</label>
                                </div>
                            </div>
                        @endif
                        <input
                            type="file"
                            name="logo"
                            id="logo"
                            class="form-control @error('logo') is-invalid @enderror"
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <div class="form-text">JPG, PNG or WebP · max 2 MB · displays at 32px height in the navbar.</div>
                        @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ----------------------------------------------------------------- --}}
        {{-- Section 2: Public Form Content --}}
        {{-- ----------------------------------------------------------------- --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">Public Form Content</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label for="welcome_title" class="form-label">Welcome Title</label>
                        <input
                            type="text"
                            name="welcome_title"
                            id="welcome_title"
                            class="form-control @error('welcome_title') is-invalid @enderror"
                            value="{{ old('welcome_title', $org->config->welcomeTitle) }}"
                            required maxlength="255"
                        >
                        @error('welcome_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="welcome_message" class="form-label">Welcome Message</label>
                        <textarea
                            name="welcome_message"
                            id="welcome_message"
                            class="form-control @error('welcome_message') is-invalid @enderror"
                            rows="2"
                            maxlength="500"
                            required
                        >{{ old('welcome_message', $org->config->welcomeMessage) }}</textarea>
                        @error('welcome_message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12 col-md-6">
                        <label for="submit_button_text" class="form-label">Submit Button Text</label>
                        <input
                            type="text"
                            name="submit_button_text"
                            id="submit_button_text"
                            class="form-control @error('submit_button_text') is-invalid @enderror"
                            value="{{ old('submit_button_text', $org->config->submitButtonText) }}"
                            required maxlength="100"
                        >
                        @error('submit_button_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label for="success_message" class="form-label">Success Message</label>
                        <textarea
                            name="success_message"
                            id="success_message"
                            class="form-control @error('success_message') is-invalid @enderror"
                            rows="2"
                            maxlength="500"
                            required
                        >{{ old('success_message', $org->config->successMessage) }}</textarea>
                        @error('success_message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ----------------------------------------------------------------- --}}
        {{-- Section 3: Field Labels --}}
        {{-- ----------------------------------------------------------------- --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">Field Labels</div>
            <div class="card-body">
                <div class="row g-3">
                    @foreach([
                        ['label_name',     'Name Field Label',     $org->config->labelName],
                        ['label_location', 'Location Field Label', $org->config->labelLocation],
                        ['label_date',     'Date Field Label',     $org->config->labelDate],
                        ['label_hours',    'Hours Field Label',    $org->config->labelHours],
                    ] as [$field, $label, $current])
                    <div class="col-12 col-sm-6">
                        <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                        <input
                            type="text"
                            name="{{ $field }}"
                            id="{{ $field }}"
                            class="form-control @error($field) is-invalid @enderror"
                            value="{{ old($field, $current) }}"
                            required maxlength="100"
                        >
                        @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ----------------------------------------------------------------- --}}
        {{-- Section 4: Form Settings --}}
        {{-- ----------------------------------------------------------------- --}}
        <div class="card mb-4">
            <div class="card-header fw-semibold">Form Settings</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12">
                        <input type="hidden" name="require_name" value="0">
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                name="require_name"
                                id="require_name"
                                value="1"
                                {{ old('require_name', $org->config->requireName) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="require_name">
                                <strong>Require name</strong>
                                <span class="text-muted fw-normal d-block small">Volunteers must enter their name to submit.</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="show_notes" value="0">
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                name="show_notes"
                                id="show_notes"
                                value="1"
                                {{ old('show_notes', $org->config->showNotes) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="show_notes">
                                <strong>Show notes field</strong>
                                <span class="text-muted fw-normal d-block small">Adds an optional free-text notes field to the form.</span>
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="show_hours_hint" value="0">
                        <div class="form-check form-switch">
                            <input
                                class="form-check-input"
                                type="checkbox"
                                role="switch"
                                name="show_hours_hint"
                                id="show_hours_hint"
                                value="1"
                                {{ old('show_hours_hint', $org->config->showHoursHint) ? 'checked' : '' }}
                            >
                            <label class="form-check-label" for="show_hours_hint">
                                <strong>Show hours format hint</strong>
                                <span class="text-muted fw-normal d-block small">Displays "Enter hours as a decimal (e.g., 2.5)" below the hours field.</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Save --}}
        <div class="d-flex justify-content-end gap-2 pb-5">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Save Branding Settings</button>
        </div>

    </form>
</div>
@endsection

@push('scripts')
<script>
// Keep the hex text input in sync with the colour picker
(function () {
    const picker = document.getElementById('accent_color');
    const hex    = document.getElementById('accent_color_hex');
    if (!picker || !hex) return;

    picker.addEventListener('input', () => { hex.value = picker.value; });
    hex.addEventListener('input', () => {
        if (/^#[0-9A-Fa-f]{6}$/.test(hex.value)) picker.value = hex.value;
    });
})();
</script>
@endpush
