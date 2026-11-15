@extends('layouts.admin')
@section('title', $event->exists ? 'Edit Special Event' : 'Create Special Event')
@section('content')
<div class="container-fluid" style="max-width:800px">
    <div class="page-heading"><div><p class="eyebrow">Special events</p><h1>{{ $event->exists ? 'Edit event' : 'Create an event' }}</h1><p class="text-muted">A shared purpose. Every contribution still keeps its location and date.</p></div></div>
    <form method="POST" action="{{ $event->exists ? route('admin.special-events.update', $event) : route('admin.special-events.store') }}" data-event-location-choices>
        @csrf
        @if($event->exists) @method('PUT') @endif
        <div class="card"><div class="card-body p-4">
            @if(auth()->user()->isPlatformWorkspace() && !$event->exists)
            <div class="mb-4"><label for="organization_id" class="form-label">Organization</label><select id="organization_id" name="organization_id" class="form-select" required><option value="">Choose an organization</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected(old('organization_id', request('organization_id')) == $organization->id)>{{ $organization->name }}</option>@endforeach</select></div>
            @elseif($event->exists)
            <p class="small text-muted">Organization: <strong>{{ $event->organization?->name }}</strong></p>
            @endif
            <div class="mb-4"><label for="name" class="form-label">Event name</label><input class="form-control" id="name" name="name" value="{{ old('name', $event->name) }}" placeholder="Fall Trail Cleanup 2026" required maxlength="255"></div>
            <div class="mb-4"><label for="description" class="form-label">Description <span class="text-muted">(optional)</span></label><textarea class="form-control" id="description" name="description" rows="3" maxlength="2000">{{ old('description', $event->description) }}</textarea></div>
            <div class="row g-3 mb-4">
                <div class="col-sm-6"><label for="starts_on" class="form-label">Start date</label><input type="date" class="form-control" id="starts_on" name="starts_on" required value="{{ old('starts_on', $event->starts_on?->format('Y-m-d')) }}"></div>
                <div class="col-sm-6"><label for="ends_on" class="form-label">End date <span class="text-muted">(optional)</span></label><input type="date" class="form-control" id="ends_on" name="ends_on" value="{{ old('ends_on', $event->ends_on?->format('Y-m-d')) }}"><div class="form-text">Leave blank for a single-day event.</div></div>
            </div>
            @php
                $selectedLocations = (array) old('location_ids', $event->locations->pluck('id')->all());
                $locationMode = old('location_mode', $event->locations->isNotEmpty() ? 'selected' : 'any');
            @endphp
            <fieldset class="mb-4">
                <legend class="form-label fs-6 fw-semibold">Event locations</legend>
                <div class="form-check"><input class="form-check-input" type="radio" name="location_mode" id="location-any" value="any" @checked($locationMode === 'any')><label class="form-check-label" for="location-any">Location not important</label></div>
                <div class="form-text mb-2">Volunteers may choose any available organization location.</div>
                <div class="form-check"><input class="form-check-input" type="radio" name="location_mode" id="location-selected" value="selected" @checked($locationMode === 'selected')><label class="form-check-label" for="location-selected">Choose one or more locations</label></div>
                <div class="form-text mb-2">New hours for this event must use one of the selected locations. Existing hours remain unchanged.</div>
                <div class="border rounded p-3" data-location-choices>
                    @forelse($locations as $location)
                    <div class="form-check" data-location-choice data-org="{{ $location->organization_id }}">
                        <input class="form-check-input" type="checkbox" name="location_ids[]" id="event-location-{{ $location->id }}" value="{{ $location->id }}" @checked(in_array($location->id, $selectedLocations))>
                        <label class="form-check-label" for="event-location-{{ $location->id }}">{{ $location->name }}{{ $location->is_active ? '' : ' (inactive)' }}</label>
                    </div>
                    @empty
                    <p class="text-muted mb-0">No locations have been added yet.</p>
                    @endforelse
                    <p class="form-text mb-0" data-location-choices-status></p>
                </div>
                @if($event->locations->contains(fn ($location) => $location->trashed()))
                <p class="text-warning small mt-2">A previously assigned location was deleted. Select an available location or choose location not important.</p>
                @endif
            </fieldset>
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" @checked(old('is_active', $event->is_active))><label class="form-check-label" for="is_active">Available on the public form</label></div>
            <p class="form-text">Deactivate when logging is complete. Historical hours and reports are preserved.</p>
        </div></div>
        <div class="d-flex gap-2 mt-4"><button class="btn btn-primary">{{ $event->exists ? 'Save event' : 'Create event' }}</button><a class="btn btn-outline-secondary" href="{{ route('admin.special-events.index') }}">Cancel</a></div>
    </form>
</div>
@endsection
