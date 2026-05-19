{{--
    Shared form fields for creating and editing an Organization.
    Expected variables:
      $organization  — model instance (new or existing)
      $defaults      — OrganizationSettings::defaults() (only used on create when $organization has no settings)
      $isSuperAdmin  — always true (this partial is only rendered for super-admins)
--}}

@php
    $cfg = isset($organization) && $organization->exists
        ? $organization->config
        : $defaults;
@endphp

{{-- ----------------------------------------------------------------- --}}
{{-- Identity --}}
{{-- ----------------------------------------------------------------- --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">Identity</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12 col-md-7">
                <label for="name" class="form-label">Organization Name</label>
                <input type="text" name="name" id="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $organization->name ?? '') }}"
                    required maxlength="255">
                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-5">
                <label for="slug" class="form-label">URL Slug</label>
                <div class="input-group">
                    <span class="input-group-text text-muted">/</span>
                    <input type="text" name="slug" id="slug"
                        class="form-control font-monospace @error('slug') is-invalid @enderror"
                        value="{{ old('slug', $organization->slug ?? '') }}"
                        required maxlength="100"
                        pattern="[A-Za-z0-9_-]+"
                        placeholder="my-org">
                </div>
                <div class="form-text">Letters, numbers, hyphens and underscores only. Cannot be changed lightly — it's the public URL.</div>
                @error('slug') <div class="invalid-feedback mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label for="accent_color" class="form-label">Accent Colour</label>
                <div class="input-group">
                    <input type="color" name="accent_color" id="accent_color"
                        class="form-control form-control-color @error('accent_color') is-invalid @enderror"
                        value="{{ old('accent_color', $organization->accent_color ?? '#0d6efd') }}"
                        title="Choose brand colour">
                    <input type="text" id="accent_color_hex" class="form-control font-monospace"
                        value="{{ old('accent_color', $organization->accent_color ?? '#0d6efd') }}"
                        maxlength="7" aria-label="Hex colour">
                </div>
                @error('accent_color') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <div class="col-12 col-md-4">
                <label class="form-label d-block">Status</label>
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" type="checkbox" role="switch"
                        name="is_active" id="is_active" value="1"
                        {{ old('is_active', $organization->is_active ?? true) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
                <div class="form-text">Inactive orgs return 404 on their public URL.</div>
            </div>

            <div class="col-12">
                <label class="form-label">Logo</label>
                @if(isset($organization) && $organization->logo_url)
                    <div class="mb-2 d-flex align-items-center gap-3">
                        <img src="{{ $organization->logo_url }}" alt="Current logo"
                             style="height:48px;width:auto;object-fit:contain;background:#f8f9fa;padding:4px;border-radius:4px;border:1px solid #dee2e6;">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remove_logo" id="remove_logo" value="1">
                            <label class="form-check-label text-danger small" for="remove_logo">Remove current logo</label>
                        </div>
                    </div>
                @endif
                <input type="file" name="logo" id="logo"
                    class="form-control @error('logo') is-invalid @enderror"
                    accept="image/jpeg,image/png,image/webp,image/svg+xml">
                <div class="form-text">JPG, PNG, WebP or SVG · max 2 MB</div>
                @error('logo') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

{{-- ----------------------------------------------------------------- --}}
{{-- Public Form Content --}}
{{-- ----------------------------------------------------------------- --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">Public Form Content</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-12">
                <label for="welcome_title" class="form-label">Welcome Title</label>
                <input type="text" name="welcome_title" id="welcome_title"
                    class="form-control @error('welcome_title') is-invalid @enderror"
                    value="{{ old('welcome_title', $cfg->welcomeTitle) }}"
                    required maxlength="255">
                @error('welcome_title') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label for="welcome_message" class="form-label">Welcome Message</label>
                <textarea name="welcome_message" id="welcome_message" rows="2" maxlength="500" required
                    class="form-control @error('welcome_message') is-invalid @enderror"
                >{{ old('welcome_message', $cfg->welcomeMessage) }}</textarea>
                @error('welcome_message') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12 col-md-6">
                <label for="submit_button_text" class="form-label">Submit Button Text</label>
                <input type="text" name="submit_button_text" id="submit_button_text"
                    class="form-control @error('submit_button_text') is-invalid @enderror"
                    value="{{ old('submit_button_text', $cfg->submitButtonText) }}"
                    required maxlength="100">
                @error('submit_button_text') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-12">
                <label for="success_message" class="form-label">Success Message</label>
                <textarea name="success_message" id="success_message" rows="2" maxlength="500" required
                    class="form-control @error('success_message') is-invalid @enderror"
                >{{ old('success_message', $cfg->successMessage) }}</textarea>
                @error('success_message') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>
    </div>
</div>

{{-- ----------------------------------------------------------------- --}}
{{-- Field Labels --}}
{{-- ----------------------------------------------------------------- --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">Field Labels</div>
    <div class="card-body">
        <div class="row g-3">
            @foreach([
                ['label_name',     'Name Label',     $cfg->labelName],
                ['label_location', 'Location Label', $cfg->labelLocation],
                ['label_date',     'Date Label',     $cfg->labelDate],
                ['label_hours',    'Hours Label',    $cfg->labelHours],
            ] as [$field, $label, $current])
            <div class="col-12 col-sm-6">
                <label for="{{ $field }}" class="form-label">{{ $label }}</label>
                <input type="text" name="{{ $field }}" id="{{ $field }}"
                    class="form-control @error($field) is-invalid @enderror"
                    value="{{ old($field, $current) }}"
                    required maxlength="100">
                @error($field) <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- ----------------------------------------------------------------- --}}
{{-- Form Settings --}}
{{-- ----------------------------------------------------------------- --}}
<div class="card mb-4">
    <div class="card-header fw-semibold">Form Settings</div>
    <div class="card-body">
        @foreach([
            ['require_name',    $cfg->requireName,    'Require name',         'Volunteers must enter their name to submit.'],
            ['show_notes',      $cfg->showNotes,      'Show notes field',     'Adds an optional free-text notes field.'],
            ['show_hours_hint', $cfg->showHoursHint,  'Show hours hint',      'Displays the decimal format hint below the hours field.'],
        ] as [$field, $value, $label, $hint])
        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" role="switch"
                name="{{ $field }}" id="{{ $field }}" value="1"
                {{ old($field, $value) ? 'checked' : '' }}>
            <label class="form-check-label" for="{{ $field }}">
                <strong>{{ $label }}</strong>
                <span class="text-muted fw-normal d-block small">{{ $hint }}</span>
            </label>
        </div>
        @endforeach
    </div>
</div>
