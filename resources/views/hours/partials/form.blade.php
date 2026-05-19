@php
    $config = $org->config;
    $volunteerUser = (auth()->check() && auth()->user()->isVolunteer()) ? auth()->user() : null;
@endphp

<!-- Volunteer Account Bar -->
@if($volunteerUser)
<div class="alert alert-info d-flex align-items-center justify-content-between py-2 mb-3" role="status">
    <span>
        <strong>{{ $volunteerUser->name }}</strong>
        &mdash; you&rsquo;re signed in
    </span>
    <span class="d-flex gap-3">
        <a href="{{ route('org.account.history', $org) }}" class="alert-link text-nowrap">My history</a>
        <form method="POST" action="{{ route('org.account.logout', $org) }}" class="d-inline m-0">
            @csrf
            <button type="submit" class="btn btn-link alert-link p-0 border-0">Sign out</button>
        </form>
    </span>
</div>
@else
<div class="text-center text-muted small mb-3">
    Have an account?
    <a href="{{ route('org.account.login', $org) }}">Sign in</a>
    or
    <a href="{{ route('org.account.register', $org) }}">create one</a>
    to track your history.
</div>
@endif

<!-- Main Form Card -->
<div class="card">
    <div class="card-header">
        <h2 class="h5 mb-0">Volunteer Entry</h2>
    </div>

    <div class="card-body">
        <form
            method="POST"
            action="{{ route('org.hours.store', $org) }}"
            hx-post="{{ route('org.hours.store', $org) }}"
            hx-target="#form-container"
            hx-swap="innerHTML"
            novalidate
        >
            @csrf
            {{-- Anonymous continuity token: client-generated UUID stored in localStorage.
                 Never contains personal data. Lets us estimate unique anonymous volunteers.
                 Omitted entirely for signed-in users (cleared server-side if present). --}}
            @if(! $volunteerUser)
            <input type="hidden" name="anon_token" id="anon_token">
            @endif

            <!-- Location Field -->
            <div class="mb-3">
                <label for="location_id" class="form-label fw-semibold">
                    {{ $config->labelLocation }} <span class="text-danger">*</span>
                </label>
                <select
                    name="location_id"
                    id="location_id"
                    class="form-select @error('location_id') is-invalid @enderror"
                    required
                    aria-describedby="locationHelp"
                >
                    <option value="">Select a {{ strtolower($config->labelLocation) }}...</option>
                    @foreach($locations as $location)
                        <option
                            value="{{ $location->id }}"
                            {{ old('location_id') == $location->id ? 'selected' : '' }}
                        >{{ $location->name }}</option>
                    @endforeach
                </select>
                @error('location_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="form-text" id="locationHelp">Choose where you volunteered</div>
                @enderror
            </div>

            <!-- Date Field -->
            <div class="mb-3">
                <label for="volunteered_at" class="form-label fw-semibold">
                    {{ $config->labelDate }} <span class="text-danger">*</span>
                </label>
                <input
                    type="date"
                    name="volunteered_at"
                    id="volunteered_at"
                    class="form-control @error('volunteered_at') is-invalid @enderror"
                    value="{{ old('volunteered_at', date('Y-m-d')) }}"
                    max="{{ date('Y-m-d') }}"
                    required
                    aria-describedby="dateHelp"
                >
                @error('volunteered_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="form-text" id="dateHelp">When did you volunteer?</div>
                @enderror
            </div>

            <!-- Hours Field -->
            <div class="mb-3">
                <label for="hours" class="form-label fw-semibold">
                    {{ $config->labelHours }} <span class="text-danger">*</span>
                </label>
                <input
                    type="number"
                    name="hours"
                    id="hours"
                    class="form-control @error('hours') is-invalid @enderror"
                    value="{{ old('hours') }}"
                    min="0.01"
                    max="24"
                    step="0.25"
                    placeholder="4.5"
                    required
                    aria-describedby="hoursHelp"
                >
                @error('hours')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    @if($config->showHoursHint)
                        <div class="form-text" id="hoursHelp">Enter hours as a decimal (e.g., 2.5 for 2&frac12; hours)</div>
                    @endif
                @enderror
            </div>

            <!-- Name Field -->
            <div class="mb-3">
                <label for="name" class="form-label fw-semibold">
                    {{ $config->labelName }}
                    @if(!$config->requireName)
                        <span class="text-muted fw-normal">(optional)</span>
                    @else
                        <span class="text-danger">*</span>
                    @endif
                </label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', $volunteerUser?->name ?? '') }}"
                    maxlength="255"
                    placeholder="{{ $config->requireName ? '' : 'Anonymous volunteer' }}"
                    {{ $config->requireName ? 'required' : '' }}
                    aria-describedby="nameHelp"
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    @if($volunteerUser)
                        <div class="form-text" id="nameHelp">Pre-filled from your account — you may edit it for this entry</div>
                    @elseif(!$config->requireName)
                        <div class="form-text" id="nameHelp">Leave blank to log hours anonymously</div>
                    @endif
                @enderror
            </div>

            @if($config->showNotes)
            <!-- Notes Field -->
            <div class="mb-3">
                <label for="notes" class="form-label fw-semibold">
                    Notes <span class="text-muted fw-normal">(optional)</span>
                </label>
                <textarea
                    name="notes"
                    id="notes"
                    class="form-control @error('notes') is-invalid @enderror"
                    rows="3"
                    maxlength="1000"
                    placeholder="Describe what you worked on..."
                >{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @endif

            <!-- Submit Button -->
            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2" style="vertical-align:-3px;" aria-hidden="true">
                        <path d="M5 13L9 17L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ $config->submitButtonText }}
                </button>
            </div>
        </form>
    </div>
</div>
