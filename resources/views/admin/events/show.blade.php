@extends('layouts.admin')
@section('title', $event->name)
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading"><div><p class="eyebrow">{{ $event->organization?->name }} / Special event</p><h1>{{ $event->name }}</h1><p class="text-muted">{{ $event->starts_on->format('F j, Y') }}@if($event->ends_on) &ndash; {{ $event->ends_on->format('F j, Y') }}@endif &middot; {{ $event->is_active ? 'Active' : 'Inactive' }}</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.special-events.edit', $event) }}">Edit event</a></div>
    <div class="row g-4 mb-4">
        <div class="col-md-4"><div class="card h-100"><div class="card-body"><p class="eyebrow">Community contribution</p><div class="display-5 fw-semibold">{{ number_format($event->volunteer_entries_sum_hours ?? 0, 1) }}</div><p class="text-muted mb-0">hours across {{ $event->volunteer_entries_count }} submissions</p></div></div></div>
        <div class="col-md-8"><div class="card h-100"><div class="card-body"><h2 class="h5">About this event</h2><p class="text-muted" style="white-space:pre-line">{{ $event->description ?: 'No description yet. Add a little context for your volunteers.' }}</p><a href="{{ route('admin.reports', ['special_event_id' => $event->id, 'organization_id' => $event->organization_id]) }}" class="btn btn-outline-primary">View event report</a><a class="btn btn-link" href="{{ route('admin.dashboard', ['special_event_id' => $event->id, 'organization_id' => $event->organization_id]) }}">View attributed hours</a></div></div></div>
    </div>
    @if($event->organization && $event->is_active && $event->organization->is_active)
    <section class="share-panel">
        <p class="eyebrow">Invite your crew</p><h2 class="h4">One link. The event is already selected.</h2><p class="text-muted">Volunteers choose their location, enter their hours, and they're done. They can still choose ordinary volunteering instead.</p>
        <label for="event-url" class="visually-hidden">Event logging link</label>
        <div class="d-flex flex-wrap gap-2"><input id="event-url" class="form-control share-url" readonly value="{{ route('org.hours.index', ['org' => $event->organization, 'event' => $event->id]) }}"><button type="button" class="btn btn-primary" data-copy-target="event-url">Copy event link</button><a target="_blank" rel="noopener" class="btn btn-outline-secondary" href="{{ route('org.hours.index', ['org' => $event->organization, 'event' => $event->id]) }}">Preview form</a></div><p class="small mt-2 mb-0" data-copy-status role="status"></p>
    </section>
    @else
    <div class="alert alert-secondary">This event is not available for new public submissions. Existing contributions remain in reports.</div>
    @endif
</div>
@endsection
