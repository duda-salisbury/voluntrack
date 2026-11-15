<div class="volunteer-confirmation">
    <h2 class="h3 mb-2">Hours recorded</h2>
    <p class="text-muted mb-3">{{ $org->config->successMessage }}</p>
    @php $durationMinutes = (int) round((float) $entry->hours * 60); @endphp
    <div class="volunteer-context mb-3">
        <div>
            <strong>{{ intdiv($durationMinutes, 60) }} h {{ $durationMinutes % 60 }} min &middot; {{ $org->name }}</strong>
            <div class="text-muted small">{{ $entry->volunteered_at->format('M j, Y') }} &middot; {{ $entry->location?->name }}@if($entry->specialEvent) &middot; {{ $entry->specialEvent->name }}@endif</div>
            @if($entry->name)<div class="text-muted small">{{ $entry->name }}</div>@endif
        </div>
    </div>
    <div class="d-grid">
        <a href="{{ route('org.hours.index', $org) }}" class="btn btn-log"
            hx-get="{{ route('org.hours.index', $org) }}" hx-target="#form-container" hx-select="#form-container" hx-swap="outerHTML">Log More Hours</a>
        @auth
            <a href="{{ route('org.account.history', $org) }}" class="btn btn-outline-secondary mt-2">View my history</a>
        @endauth
    </div>
</div>
