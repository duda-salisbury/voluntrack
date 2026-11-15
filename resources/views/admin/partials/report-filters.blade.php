@php
    $presets = ['last30' => 'Last 30 days', 'this_month' => 'This month', 'last_month' => 'Last month', 'this_year' => 'This year', 'custom' => 'Custom dates'];
    $activeFilters = [];
    if (request('date_preset') && request('date_preset') !== 'custom') $activeFilters[] = $presets[request('date_preset')] ?? request('date_preset');
    elseif (request('date_from') || request('date_to')) $activeFilters[] = 'Dates: '.(request('date_from') ?: 'any start').' to '.(request('date_to') ?: 'any end');
    if (auth()->user()->isPlatformWorkspace() && request('organization_id')) $activeFilters[] = 'Organization: '.($organizations->firstWhere('id', request('organization_id'))?->name ?? 'Selected');
    if (request('location_id')) $activeFilters[] = 'Location: '.($locations->firstWhere('id', request('location_id'))?->name ?? 'Selected');
    if (request('special_event_id')) $activeFilters[] = 'Event: '.($events->firstWhere('id', request('special_event_id'))?->name ?? 'Selected');
    if (request('volunteer_user_id')) $activeFilters[] = 'Account: '.($volunteers->firstWhere('id', request('volunteer_user_id'))?->name ?? 'Selected');
    if (request('volunteer')) $activeFilters[] = 'Name contains: '.request('volunteer');
    if (request('matched')) $activeFilters[] = request('matched') === 'yes' ? 'Matching hours' : 'Not matching';
    if (request('source')) $activeFilters[] = 'Source: '.request('source');
    if (request('event_status')) $activeFilters[] = request('event_status') === 'event' ? 'Event contributions' : 'Ordinary contributions';
@endphp
<details class="card mb-4 report-filters" @if(count($activeFilters)) open @endif>
<summary class="p-3 fw-semibold">Refine this view <span class="text-muted small fw-normal ms-2">{{ count($activeFilters) ? implode(' · ', $activeFilters) : 'All dates and contributions' }}</span></summary>
<div class="card-body border-top">
    <form method="GET" action="{{ route($filterRoute) }}" class="row g-3">
        @include('admin.partials.workspace-context')
        @if(auth()->user()->isPlatformWorkspace())
        <div class="col-sm-6 col-xl-3"><label for="organization_id" class="form-label">Organization</label><select id="organization_id" name="organization_id" class="form-select"><option value="">All organizations</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected(request('organization_id') == $organization->id)>{{ $organization->name }}</option>@endforeach</select></div>
        @endif
        <div class="col-sm-6 col-xl-3"><label for="date_preset" class="form-label">Date range</label><select id="date_preset" name="date_preset" class="form-select"><option value="">All dates / custom</option>@foreach($presets as $value => $label)<option value="{{ $value }}" @selected(request('date_preset') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-xl-3"><label for="date_from" class="form-label">Custom from</label><input type="date" id="date_from" name="date_from" class="form-control" value="{{ request('date_from') }}"></div>
        <div class="col-sm-6 col-xl-3"><label for="date_to" class="form-label">Custom through</label><input type="date" id="date_to" name="date_to" class="form-control" value="{{ request('date_to') }}"></div>
        <div class="col-sm-6 col-xl-3"><label for="location_id" class="form-label">Location</label><select id="location_id" name="location_id" class="form-select"><option value="">All locations</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(request('location_id') == $location->id)>{{ $location->name }}{{ $location->trashed() ? ' (archived)' : '' }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-xl-3"><label for="special_event_id" class="form-label">Special event</label><select id="special_event_id" name="special_event_id" class="form-select"><option value="">All events and ordinary volunteering</option>@foreach($events as $event)<option value="{{ $event->id }}" @selected(request('special_event_id') == $event->id)>{{ $event->name }}{{ $event->is_active ? '' : ' (inactive)' }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-xl-3"><label for="event_status" class="form-label">Contribution type</label><select id="event_status" name="event_status" class="form-select"><option value="">All contributions</option><option value="event" @selected(request('event_status') === 'event')>Attributed to an event</option><option value="ordinary" @selected(request('event_status') === 'ordinary')>No event</option></select></div>
        <div class="col-sm-6 col-xl-3"><label for="volunteer_user_id" class="form-label">Volunteer account</label><select id="volunteer_user_id" name="volunteer_user_id" class="form-select"><option value="">All volunteers</option>@foreach($volunteers as $volunteer)<option value="{{ $volunteer->id }}" @selected(request('volunteer_user_id') == $volunteer->id)>{{ $volunteer->name }} ({{ $volunteer->email }})</option>@endforeach</select></div>
        <div class="col-sm-6 col-xl-3"><label for="volunteer" class="form-label">Submitted name</label><input id="volunteer" name="volunteer" class="form-control" value="{{ request('volunteer') }}" placeholder="Includes names without an account"></div>
        <div class="col-sm-6 col-xl-3"><label for="source" class="form-label">Submission source</label><select id="source" name="source" class="form-select"><option value="">All sources</option>@foreach($sources as $source)<option value="{{ $source }}" @selected(request('source') === $source)>{{ $source === 'manual' ? 'Public form' : ucfirst($source) }}</option>@endforeach</select></div>
        <div class="col-sm-6 col-xl-3"><label for="matched" class="form-label">Matching classification</label><select id="matched" name="matched" class="form-select"><option value="">All hours</option><option value="yes" @selected(request('matched') === 'yes')>Matching hours</option><option value="no" @selected(request('matched') === 'no')>Not classified as matching</option></select></div>
        <div class="col-12 d-flex flex-wrap align-items-center gap-2"><button class="btn btn-primary">Apply filters</button><a class="btn btn-outline-secondary" href="{{ route($filterRoute) }}">Clear filters</a><span class="small text-muted ms-sm-2">Custom dates apply when no preset or Custom dates is selected. All summaries follow these filters.</span></div>
    </form>
</div></details>
