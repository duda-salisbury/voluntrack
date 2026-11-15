@extends('layouts.app')

@section('title', 'Find your organization')

@section('content')
<div class="container landing">
    <div class="landing-hero">
        <p class="eyebrow">A little time. A lasting impact.</p>
        <h1>Make every hour count.</h1>
        <p class="landing-intro">Find your organization and log the time you give to your community. No account needed.</p>
        @if(auth()->check() && ! request()->boolean('find'))
            <div class="alert alert-warning text-start" role="status">Your organization is unavailable or not assigned to your account. Contact your platform administrator, or find an active organization below.</div>
        @endif
        <form action="{{ route('home') }}" method="GET" class="landing-search"
              hx-get="{{ route('home') }}" hx-trigger="input changed delay:300ms from:#org-search, search from:#org-search, submit"
              hx-target="#org-results" hx-indicator="#search-loading" hx-sync="this:replace">
            @if(request()->boolean('find'))<input type="hidden" name="find" value="1">@endif
            <label for="org-search" class="form-label">Find your organization</label>
            <div class="search-input">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="m16 16 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                <input type="search" name="q" id="org-search" class="form-control" value="{{ $search }}" placeholder="Start typing an organization name" autocomplete="off" aria-describedby="search-help">
                <button class="btn btn-primary" type="submit">Search</button>
            </div>
            <p id="search-help" class="form-text">Enter at least 2 characters to search.</p>
        </form>
        <div id="search-loading" class="htmx-indicator search-loading" role="status"><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> Looking for organizations...</div>
        <div id="org-results" class="landing-results" aria-live="polite">
            @include('home-results')
        </div>
    </div>
    <div class="landing-footer"><span>Made for people who show up.</span><a href="{{ route('login') }}">Administrator sign in &rarr;</a></div>
</div>
@endsection
