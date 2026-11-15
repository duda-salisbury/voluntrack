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
        <h1>Track volunteer hours for your organization.</h1>
        <p class="landing-intro">Log hours, manage events, and report on volunteer contributions.</p>
        @if(auth()->check() && ! request()->boolean('find'))
            <div class="alert alert-warning mt-3" role="status">Your organization is unavailable or not assigned to your account. Contact your platform administrator, or find an active organization below.</div>
        @endif
    </div>
    <section class="card landing-search" aria-labelledby="search-title">
        <div class="card-body">
            <h2 id="search-title">Log volunteer hours</h2>
            <p class="landing-search-intro">Find your organization and open its volunteer form.</p>
            <form action="{{ route('home') }}" method="GET" data-organization-search
                  hx-get="{{ route('home') }}" hx-trigger="input changed delay:300ms from:#org-search, search from:#org-search, submit"
                  hx-target="#org-results" hx-indicator="#search-loading" hx-sync="this:replace">
                @if(request()->boolean('find'))<input type="hidden" name="find" value="1">@endif
                <label for="org-search" class="visually-hidden">Organization name</label>
                <div class="search-controls">
                    <div class="search-input">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <input type="search" name="q" id="org-search" class="form-control" value="{{ $search }}" placeholder="Search for an organization..." autocomplete="off" maxlength="255" aria-describedby="search-help">
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
        <h2 class="visually-hidden" id="features-title">Voluntrack features</h2>
        <div class="landing-feature-grid">
            <div class="landing-feature">
                <span class="landing-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                <div><h3>Log hours</h3><p>Volunteers can record time from any device.</p></div>
            </div>
            <div class="landing-feature">
                <span class="landing-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/></svg></span>
                <div><h3>Events &amp; locations</h3><p>Track work by event, workday, or location.</p></div>
            </div>
            <div class="landing-feature">
                <span class="landing-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 20v-5M12 20V9M19 20V3"/></svg></span>
                <div><h3>Reports &amp; exports</h3><p>See totals and export records for reporting.</p></div>
            </div>
            <div class="landing-feature">
                <span class="landing-feature-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="7" r="3"/><path d="M5 20v-1a7 7 0 0 1 14 0v1M5 8a2 2 0 1 0 0 4M19 8a2 2 0 1 1 0 4M2 19a5 5 0 0 1 3-4.6M22 19a5 5 0 0 0-3-4.6"/></svg></span>
                <div><h3>Volunteer management</h3><p>Review contributions and manage access.</p></div>
            </div>
        </div>
    </section>
    <section class="landing-onboarding" aria-labelledby="onboarding-title">
        <span class="landing-onboarding-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="7" r="3"/><path d="M5 20v-1a7 7 0 0 1 14 0v1M5 8a2 2 0 1 0 0 4M19 8a2 2 0 1 1 0 4M2 19a5 5 0 0 1 3-4.6M22 19a5 5 0 0 0-3-4.6"/></svg></span>
        <div class="landing-onboarding-copy">
            <h2 id="onboarding-title">Use Voluntrack for your organization.</h2>
            <p>Set up volunteer hour tracking for your group. Free during early access.</p>
        </div>
        <a href="{{ route('organization-requests.create') }}" class="btn btn-primary">Get started</a>
    </section>
</div>
@endsection
