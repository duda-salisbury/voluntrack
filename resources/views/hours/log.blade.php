@extends('layouts.app')

@section('title', $org->config->welcomeTitle)

@section('content')
<div class="container" style="max-width: 640px;">
    <div class="text-center mb-4">
        <h1 class="mb-1">{{ $org->config->welcomeTitle }}</h1>
        <p class="text-muted">{{ $org->config->welcomeMessage }}</p>
    </div>

    <div id="form-container">
        @if(isset($htmxPartial) && $htmxPartial)
            @if(isset($showSuccess) && $showSuccess)
                @include('hours.partials.success-inline')
            @else
                @include('hours.partials.form')
            @endif
        @else
            @include('hours.partials.form')
        @endif
    </div>

    <div class="card mt-4">
        <div class="card-body d-flex gap-3">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" class="text-success flex-shrink-0 mt-1" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M12 16V12M12 8H12.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <div>
                <h6 class="mb-1">Why track hours?</h6>
                <p class="text-muted small mb-0">Your volunteer hours help secure grants, demonstrate community impact, and show the value of the work being done.</p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{--
    Anonymous continuity token
    ──────────────────────────
    Generates a random UUID once and stores it in localStorage under 'vt_anon'.
    The token is injected into the hidden #anon_token field before every submission
    (including HTMX re-renders that replace the form in-place).

    Privacy notes:
    • Pure random UUID — no personal data, no device fingerprinting.
    • First-party only: never shared with third parties.
    • Scoped to this domain's localStorage; cleared with "Clear site data".
    • Logged-in volunteers don't send the token (field is absent from their form).
--}}
<script>
(function () {
    var KEY   = 'vt_anon';
    var token = localStorage.getItem(KEY);

    if (!token) {
        // crypto.randomUUID() — available in all modern browsers (Chrome 92+, FF 95+, Safari 15.4+)
        token = (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function')
            ? crypto.randomUUID()
            : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0;
                return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
              });
        localStorage.setItem(KEY, token);
    }

    function applyToken() {
        var el = document.getElementById('anon_token');
        if (el) { el.value = token; }
    }

    // Set on initial page load
    applyToken();

    // Re-set after every HTMX swap (the form partial is replaced in-place)
    document.addEventListener('htmx:afterSwap', applyToken);
}());
</script>
@endpush
