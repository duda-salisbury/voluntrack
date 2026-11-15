@extends('layouts.app')
@section('title', 'Request organization access')
@section('content')
<div class="container" style="max-width: 760px;">
    <div class="mb-4">
        <p class="eyebrow">Get started</p>
        <h1 class="h2">Request organization access</h1>
        <p class="text-muted mb-1">Tell us about your organization. A platform administrator will review your request.</p>
        <p class="small text-muted mb-0">Voluntrack is free during early access. No payment information required.</p>
    </div>
    @if($errors->any())
        <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif
    @guest
        <p class="small">Already have an account? <a href="{{ route('organization-requests.authenticate') }}">Sign in or create an account</a> now, or complete the form first.</p>
    @endguest
    <form method="POST" action="{{ route('organization-requests.store') }}" class="card">
        @csrf
        <div class="card-body">
            <fieldset class="mb-3">
                <legend class="fs-6 fw-semibold">What would you like to do?</legend>
                <div class="form-check"><input class="form-check-input" type="radio" name="type" id="type-new" value="new" @checked(old('type', $draft['type'] ?? 'new') === 'new')><label class="form-check-label" for="type-new">Set up a new organization</label></div>
                <div class="form-check"><input class="form-check-input" type="radio" name="type" id="type-existing" value="existing" @checked(old('type', $draft['type'] ?? 'new') === 'existing')><label class="form-check-label" for="type-existing">Request administrator access to an existing organization</label></div>
            </fieldset>
            <div class="mb-3">
                <label for="organization-name" class="form-label fw-semibold">Organization name</label>
                <input id="organization-name" name="organization_name" class="form-control @error('organization_name') is-invalid @enderror" value="{{ old('organization_name', $draft['organization_name'] ?? '') }}" maxlength="160" required aria-describedby="organization-name-help">
                <div id="organization-name-help" class="form-text">For an existing organization, enter its exact name. Access requires verification.</div>
                @error('organization_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="website" class="form-label fw-semibold">Website <span class="fw-normal text-muted">(optional)</span></label>
                <input type="url" id="website" name="website" class="form-control @error('website') is-invalid @enderror" value="{{ old('website', $draft['website'] ?? '') }}" maxlength="2048" placeholder="https://example.org">
                @error('website')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6"><label for="contact-name" class="form-label fw-semibold">Contact name</label><input id="contact-name" name="contact_name" class="form-control @error('contact_name') is-invalid @enderror" value="{{ old('contact_name', $draft['contact_name'] ?? auth()->user()?->name) }}" autocomplete="name" maxlength="255" required>@error('contact_name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label for="contact-email" class="form-label fw-semibold">Contact email</label><input type="email" id="contact-email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email', $draft['contact_email'] ?? auth()->user()?->email) }}" autocomplete="email" maxlength="255" required>@error('contact_email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            </div>
            <div class="mb-4"><label for="description" class="form-label fw-semibold">Brief description <span class="fw-normal text-muted">(optional)</span></label><textarea id="description" name="description" class="form-control @error('description') is-invalid @enderror" rows="3" maxlength="1000">{{ old('description', $draft['description'] ?? '') }}</textarea>@error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <button type="submit" class="btn btn-primary">{{ auth()->check() ? 'Submit request' : 'Continue' }}</button>
            @auth<a class="ms-3" href="{{ route('organization-requests.index') }}">My requests</a>@endauth
            @guest<p class="small text-muted mt-2 mb-0">Sign in or create an account before submitting. Your details will be saved for this session.</p>@endguest
        </div>
    </form>
</div>
@endsection
