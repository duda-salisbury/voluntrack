@extends('layouts.app')

@section('title', 'Find your organization')
@section('body-class', 'home-page')
@section('footer-text', 'Voluntrack helps organizations collect volunteer hours.')

@push('header-actions')
    @guest<a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary">Sign in</a>@endguest
@endpush

@section('content')
<div class="container landing">
    <div class="landing-copy">
        <p class="eyebrow">Voluntrack</p>
        <h1>Find your organization.</h1>
        <p class="landing-intro">Log volunteer hours quickly and get back to your day. No account required.</p>
        <p class="text-muted mb-0">Search to find your organization and open its volunteer form.</p>
        @if(auth()->check() && ! request()->boolean('find'))
            <div class="alert alert-warning mt-3" role="status">Your organization is unavailable or not assigned to your account. Contact your platform administrator, or find an active organization below.</div>
        @endif
    </div>
    <section class="card landing-search" aria-labelledby="search-title">
        <div class="card-body">
            <h2 class="h5 mb-3" id="search-title">Search organizations</h2>
            <form action="{{ route('home') }}" method="GET" data-organization-search
                  hx-get="{{ route('home') }}" hx-trigger="input changed delay:300ms from:#org-search, search from:#org-search, submit"
                  hx-target="#org-results" hx-indicator="#search-loading" hx-sync="this:replace">
                @if(request()->boolean('find'))<input type="hidden" name="find" value="1">@endif
                <label for="org-search" class="visually-hidden">Organization name</label>
                <div class="search-controls">
                    <div class="search-input">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <input type="search" name="q" id="org-search" class="form-control" value="{{ $search }}" placeholder="Start typing an organization name" autocomplete="off" maxlength="255" aria-describedby="search-help">
                    </div>
                    <button class="btn btn-primary" type="submit">Search</button>
                </div>
                <p id="search-help" class="form-text mb-0">Enter at least 2 characters to search.</p>
            </form>
            <div id="search-loading" class="htmx-indicator search-loading" role="status"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Searching organizations...</div>
            <div id="search-error" class="alert alert-danger mt-3 d-none" role="alert">Search is unavailable. Please try again.</div>
            <div id="org-results" class="landing-results" aria-live="polite">
                @include('home-results')
            </div>
        </div>
    </section>
</div>
<div class="container landing-details">
    <section class="landing-features" aria-labelledby="features-title">
        <h2 class="h3 mb-2" id="features-title">Volunteer tracking, without the busywork.</h2>
        <p class="text-muted mb-4">A simple way for organizations to collect volunteer hours and keep track of the work getting done.</p>
        <div class="landing-feature-grid">
            <div class="landing-feature">
                <span class="landing-feature-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <div><h3 class="h6 mb-1">Easy hour logging</h3><p>Volunteers can record hours from their phones, with or without an account.</p></div>
            </div>
            <div class="landing-feature">
                <span class="landing-feature-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                <div><h3 class="h6 mb-1">Events and locations</h3><p>Organize volunteer activity by workday, event, or location.</p></div>
            </div>
            <div class="landing-feature">
                <span class="landing-feature-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 3h10l4 4v14H5z"/><path d="M15 3v5h4M8 12h8M8 16h8"/></svg></span>
                <div><h3 class="h6 mb-1">Reports and exports</h3><p>See where volunteer time goes and export records for reporting.</p></div>
            </div>
            <div class="landing-feature">
                <span class="landing-feature-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3"/><path d="M3 20v-1a6 6 0 0 1 12 0v1M16 5a3 3 0 0 1 0 6M18 14a5 5 0 0 1 3 5v1"/></svg></span>
                <div><h3 class="h6 mb-1">Volunteer management</h3><p>Review contributions, manage access, and track participation.</p></div>
            </div>
        </div>
    </section>
    <section class="landing-onboarding" aria-labelledby="onboarding-title">
        <div>
            <h2 class="h5 mb-1" id="onboarding-title">Bring your organization to Voluntrack</h2>
            <p class="text-muted mb-1">Set up volunteer hour tracking for your organization.</p>
            <p class="small text-muted mb-0">Free during early access. No payment information required.</p>
        </div>
        <a href="{{ route('organization-requests.create') }}" class="btn btn-primary">Get Started</a>
    </section>
</div>
@endsection
