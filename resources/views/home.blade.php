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
            <div class="border-top mt-4 pt-3"><h2 class="h6 mb-1">Want to use Voluntrack for your organization?</h2><p class="small text-muted mb-1">Set up volunteer hour tracking for your group.</p><a href="{{ route('organization-requests.create') }}">Get started &rarr;</a></div>
        </div>
    </section>
</div>
@endsection
