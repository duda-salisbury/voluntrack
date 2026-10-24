@extends('layouts.app')

@section('title', 'Log Volunteer Hours')

@section('content')
<div class="container" style="max-width: 640px;">
    <div class="text-center mb-4">
        <p class="eyebrow">{{ $org->name }} / Every hour matters</p>
        <h1 class="mb-2">{{ $org->config->welcomeTitle }}</h1>
        <p class="text-muted">{{ $org->config->welcomeMessage }}</p>
    </div>

    <div id="form-container">
        @if(auth()->check() && auth()->user()->isVolunteer() && auth()->user()->organization_id !== $org->id)
            <p class="small text-muted">Your account is associated with another organization. You can still log hours here, but they won't be linked to that account.</p>
        @endif
        @include('hours.partials.form')
    </div>

    <div class="card mt-4">
        <div class="card-body d-flex gap-3">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="text-success flex-shrink-0 mt-1">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M12 16V12M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <div>
                <h2 class="h6 mb-1">Small moments. Lasting impact.</h2>
                <p class="text-muted small mb-0">Your hours help {{ $org->name }} demonstrate community impact and support future funding. No account is needed.</p>
                <p class="text-muted small mt-2 mb-0">Log regularly? Use your browser's &ldquo;Add to Home Screen&rdquo; to keep this form handy. An internet connection is required to submit.</p>
            </div>
        </div>
    </div>
</div>
@endsection
