@extends('layouts.admin')
@section('title', $entry ? 'Edit group total' : 'Add group total')
@section('content')
@php
    $selectedEvent = $events->firstWhere('id', old('special_event_id', $entry?->special_event_id ?? request('event')));
    $eventLocation = $selectedEvent && $selectedEvent->locations->count() === 1
        ? $locations->firstWhere('id', $selectedEvent->locations->first()->id) : null;
@endphp
<div class="container-fluid workspace group-entry">
    <div class="page-heading"><div><p class="eyebrow">Volunteer hours / Group total</p><h1>{{ $entry ? 'Edit group total' : 'Add group total' }}</h1><p class="text-muted mb-0">Record combined hours without assigning them to individuals.</p></div><a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Back to volunteer hours</a></div>
    <form method="POST" action="{{ $entry ? route('admin.entries.update', $entry) : route('admin.entries.group-total.store') }}" data-group-total>
        @csrf
        @if($entry) @method('PUT') @endif
        @if($locations->isEmpty())<div class="alert alert-info">Add a location before recording a group total. <a href="{{ route('admin.locations.create') }}">Add a location</a></div>@endif
        <div class="card"><div class="card-body">
            <p class="alert alert-warning">Only record hours that haven't already been submitted individually.</p>
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="total-event">Special event <span class="text-muted fw-normal">(optional)</span></label>
                    <select id="total-event" name="special_event_id" class="form-select @error('special_event_id') is-invalid @enderror">
                        <option value="">No event</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}" data-event-date="{{ $event->starts_on->toDateString() }}" data-location-ids="{{ $event->locations->pluck('id')->join(',') }}" @selected(old('special_event_id', $entry?->special_event_id ?? request('event')) == $event->id)>{{ $event->name }} &middot; {{ $event->starts_on->format('M j, Y') }}{{ $event->is_active ? '' : ' (inactive)' }}</option>
                        @endforeach
                    </select>
                    @error('special_event_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Selecting an event fills its date and single assigned location. You can change either.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="total-date">Date</label>
                    <input id="total-date" name="volunteered_at" type="date" class="form-control @error('volunteered_at') is-invalid @enderror" value="{{ old('volunteered_at', $entry?->volunteered_at->toDateString() ?? $selectedEvent?->starts_on->toDateString() ?? today()->toDateString()) }}" required>
                    @error('volunteered_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="total-location">Location</label>
                    <select id="total-location" name="location_id" class="form-select @error('location_id') is-invalid @enderror" required>
                        <option value="">Choose a location</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" @selected(old('location_id', $entry?->location_id ?? $eventLocation?->id) == $location->id)>{{ $location->name }}{{ $location->trashed() ? ' (archived)' : ($location->is_active ? '' : ' (inactive)') }}</option>
                        @endforeach
                    </select>
                    @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="total-hours">Total volunteer-hours</label>
                    <input id="total-hours" name="hours" type="number" class="form-control @error('hours') is-invalid @enderror" value="{{ old('hours', $entry?->hours) }}" min="0.01" max="{{ \App\Models\VolunteerEntry::MAX_GROUP_HOURS }}" step="0.01" required>
                    @error('hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="total-participants">Number of participants <span class="text-muted fw-normal">(optional)</span></label>
                    <input id="total-participants" name="participant_count" type="number" class="form-control @error('participant_count') is-invalid @enderror" value="{{ old('participant_count', $entry?->participant_count) }}" min="1" max="{{ \App\Models\VolunteerEntry::MAX_GROUP_PARTICIPANTS }}" step="1">
                    @error('participant_count')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <details>
                        <summary class="fw-semibold">Calculate total from participants</summary>
                        <div class="row g-2 align-items-end mt-1">
                            <div class="col-sm-4"><label class="form-label" for="hours-per-participant">Hours per participant</label><input id="hours-per-participant" type="number" class="form-control" step="any" inputmode="decimal"></div>
                            <div class="col-sm-auto"><button type="button" class="btn btn-outline-secondary" data-calculate-group-total>Calculate total</button></div>
                        </div>
                        <p class="form-text mb-0" data-calculation-status role="status">Uses the participant count above. You can also enter the total directly.</p>
                    </details>
                </div>
                <div class="col-12"><label class="form-label" for="total-notes">Description or notes <span class="text-muted fw-normal">(optional)</span></label><textarea id="total-notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" maxlength="2000">{{ old('notes', $entry?->notes) }}</textarea>@error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="is_matched" id="total-matched" value="1" @checked(old('is_matched', $entry?->is_matched))><label class="form-check-label" for="total-matched">Mark as matched hours</label></div></div>
                @if($entry)<div class="col-12 small text-muted">Entered by {{ $entry->createdByAdmin?->name ?? 'Unavailable account' }} &middot; {{ $entry->created_at->format('M j, Y') }}</div>@endif
            </div>
        </div></div>
        <div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-primary" @disabled($locations->isEmpty())>{{ $entry ? 'Save changes' : 'Save group total' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.dashboard') }}">Cancel</a></div>
    </form>
</div>
@endsection
