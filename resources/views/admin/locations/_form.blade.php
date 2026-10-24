{{--
    Shared form fields for create and edit.
    $location is set only on edit; $organizations is a collection (may be empty for org-admins).
    $orgId is the logged-in admin's organization_id (null = super-admin).
--}}

@if($orgId === null)
<div class="mb-3">
    <label for="organization_id" class="form-label fw-semibold">
        Organization <span class="text-danger">*</span>
    </label>
    <select
        name="organization_id"
        id="organization_id"
        class="form-select @error('organization_id') is-invalid @enderror"
        required
    >
        <option value="">Select an organization…</option>
        @foreach($organizations as $org)
            <option
                value="{{ $org->id }}"
                {{ old('organization_id', $location->organization_id ?? request('organization_id')) == $org->id ? 'selected' : '' }}
            >{{ $org->name }}</option>
        @endforeach
    </select>
    @error('organization_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
@endif

<div class="mb-3">
    <label for="name" class="form-label fw-semibold">
        Name <span class="text-danger">*</span>
    </label>
    <input
        type="text"
        name="name"
        id="name"
        class="form-control @error('name') is-invalid @enderror"
        value="{{ old('name', $location->name ?? '') }}"
        required
        maxlength="255"
        autofocus
    >
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @else
        <div class="form-text">Displayed on the public volunteer form.</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label fw-semibold">
        Description <span class="text-muted fw-normal">(optional)</span>
    </label>
    <textarea
        name="description"
        id="description"
        class="form-control @error('description') is-invalid @enderror"
        rows="3"
        maxlength="1000"
        placeholder="Brief description of this location…"
    >{{ old('description', $location->description ?? '') }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<details class="mb-3">
<summary class="small text-muted mb-3">Advanced / import reference</summary>
<div class="row g-3">
    <div class="col-sm-6">
        <label for="source" class="form-label fw-semibold">
            Source <span class="text-muted fw-normal">(optional)</span>
        </label>
        <input
            type="text"
            name="source"
            id="source"
            class="form-control @error('source') is-invalid @enderror"
            value="{{ old('source', $location->source ?? 'manual') }}"
            maxlength="50"
            placeholder="manual"
        >
        @error('source')
            <div class="invalid-feedback">{{ $message }}</div>
        @else
            <div class="form-text">e.g. manual, trailforks, csv</div>
        @enderror
    </div>
    </details>
    <div class="col-sm-6">
        <label for="external_id" class="form-label fw-semibold">
            External ID <span class="text-muted fw-normal">(optional)</span>
        </label>
        <input
            type="text"
            name="external_id"
            id="external_id"
            class="form-control @error('external_id') is-invalid @enderror"
            value="{{ old('external_id', $location->external_id ?? '') }}"
            maxlength="255"
            placeholder="ID from upstream system"
        >
        @error('external_id')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-4">
    <div class="form-check form-switch">
        <input
            type="hidden"
            name="is_active"
            value="0"
        >
        <input
            class="form-check-input"
            type="checkbox"
            role="switch"
            name="is_active"
            id="is_active"
            value="1"
            {{ old('is_active', $location->is_active ?? true) ? 'checked' : '' }}
        >
        <label class="form-check-label fw-semibold" for="is_active">
            Active — visible on public volunteer form
        </label>
    </div>
</div>
