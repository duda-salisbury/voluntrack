<div class="card">
    <div class="card-body text-center py-5">
        <div class="text-success mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M8 12L11 15L16 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <h2 class="h3 mb-2">Thank You!</h2>
        <p class="text-muted mb-4">{{ $org->config->successMessage }}</p>

        @if(isset($entry))
            <div class="d-inline-flex flex-wrap gap-3 justify-content-center bg-light rounded px-4 py-2 mb-4">
                @if($entry->name)
                    <span><strong>{{ $entry->name }}</strong></span>
                    <span class="text-muted">•</span>
                @endif
                <span><strong>{{ number_format($entry->hours, 2) }}</strong> {{ strtolower($org->config->labelHours) }}</span>
                <span class="text-muted">•</span>
                <span>{{ $entry->volunteered_at->format('M j, Y') }}</span>
            </div>
        @endif

        {{-- Account nudge: only show when submitted anonymously --}}
        @if(isset($entry) && ! $entry->volunteer_user_id)
        <p class="text-muted small mb-4">
            Want to track your history?
            <a href="{{ route('org.account.register', $org) }}">Create a free account</a>
            or
            <a href="{{ route('org.account.login', $org) }}">sign in</a>.
        </p>
        @elseif(isset($entry) && $entry->volunteer_user_id)
        <p class="text-muted small mb-4">
            <a href="{{ route('org.account.history', $org) }}">View your full history &rarr;</a>
        </p>
        @endif

        <div class="d-grid mx-auto" style="max-width: 320px;">
            <button
                type="button"
                class="btn btn-primary"
                hx-get="{{ route('org.hours.index', $org) }}"
                Log Another Entry
            </button>
        </div>
    </div>
</div>
