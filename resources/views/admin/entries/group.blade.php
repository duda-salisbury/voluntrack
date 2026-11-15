@extends('layouts.admin')
@section('title', 'Add multiple volunteers')
@section('content')
<div class="container-fluid workspace group-entry">
    <div class="page-heading"><div><p class="eyebrow">Volunteer hours / Multiple volunteers</p><h1>Add multiple volunteers</h1><p class="text-muted mb-0">Choose the shared details, then add each person's contribution. Nothing is saved until you submit.</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.entries.group-total', request()->only('event')) }}">Record a group total instead</a></div>
    <form method="POST" action="{{ route('admin.entries.group.store') }}" data-group-form data-event-locations>
        @csrf
        @include('admin.partials.workspace-context')
        <section class="card mb-4"><div class="card-body"><h2 class="h5 mb-3">Shared details</h2><div class="row g-3">
            <div class="col-md-4"><label class="form-label" for="group-date">Date</label><input id="group-date" class="form-control" type="date" name="volunteered_at" value="{{ old('volunteered_at', now()->toDateString()) }}" required></div>
            <div class="col-md-4"><label class="form-label" for="group-location">Location</label><select id="group-location" class="form-select" name="location_id" required><option value="">Choose a location</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id') == $location->id)>{{ $location->name }}{{ $location->is_active ? '' : ' (inactive)' }}</option>@endforeach</select><div class="form-text" data-event-location-status role="status"></div></div>
            <div class="col-md-4"><label class="form-label" for="group-event">Special event <span class="text-muted">(optional)</span></label><select id="group-event" class="form-select" name="special_event_id"><option value="">No event</option>@foreach($events as $event)<option value="{{ $event->id }}" data-location-ids="{{ $event->locations->pluck('id')->join(',') }}" @selected(old('special_event_id', request('event')) == $event->id)>{{ $event->name }} &middot; {{ $event->starts_on->format('M j, Y') }}</option>@endforeach</select></div>
        </div></div></section>
        <section class="card"><div class="card-body"><div class="section-heading"><div><h2 class="h5">Contributions</h2><p class="text-muted small mb-0">Enter a name and a duration for each person. Names do not create or link accounts.</p></div></div>
            <div data-group-rows>
                @foreach(old('rows', [['name' => '', 'hours' => '', 'minutes' => '']]) as $index => $row)
                    @include('admin.entries.group-row', ['index' => $index, 'row' => $row])
                @endforeach
            </div>
            <template data-group-template>@include('admin.entries.group-row', ['index' => '__INDEX__', 'row' => []])</template>
            <button type="button" class="btn btn-outline-secondary mt-3" data-add-group-row>Add another person</button>
            <p class="form-text">Up to 50 contributions at a time. Review the rows before submitting.</p>
        </div></section>
        <div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-primary" @disabled($locations->isEmpty())>Save group contributions</button><a class="btn btn-outline-secondary" href="{{ route('admin.dashboard') }}">Cancel</a></div>
    </form>
</div>
@endsection
