@php
    $selectedEvent = $events->firstWhere('id', old('special_event_id', request('event')));
    $eventLocation = $selectedEvent && $selectedEvent->locations->count() === 1
        ? $locations->firstWhere('id', $selectedEvent->locations->first()->id)
        : null;
    $selectedLocationId = old('location_id', $eventLocation?->id);
    $selectedDate = old('volunteered_at', $selectedEvent?->starts_on->toDateString() ?? today()->toDateString());
@endphp
<form
    method="POST"
    action="{{ route('org.hours.store', $org) }}"
    hx-post="{{ route('org.hours.store', $org) }}"
    hx-target="#form-container"
    hx-select="#form-container"
    hx-swap="outerHTML"
    hx-disabled-elt="find button[type='submit']"
    data-event-locations
    data-volunteer-context
    data-has-errors="{{ $errors->any() ? 'true' : 'false' }}"
>
    @csrf
    <input type="hidden" name="anon_token" value="{{ old('anon_token') }}" data-anon-token>
    @if($errors->any())
        <div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary><h2 class="h6">A few details need your attention</h2><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
    @endif
    @if($locations->isEmpty())
        <div class="alert alert-info">This organization is getting ready to welcome volunteers. Check back once a location has been added.</div>
    @endif

    <div class="volunteer-context mb-3" data-context-summary hidden>
        <div>
            <strong data-context-event></strong>
            <div class="text-muted small" data-context-detail></div>
        </div>
        <button type="button" class="volunteer-context-change" data-context-toggle aria-controls="volunteer-context-fields" aria-expanded="false">Change</button>
    </div>

    <div id="volunteer-context-fields" data-context-fields>
        <div class="row g-2 g-md-3 mb-3">
            <div class="col-md-4">
                <label for="volunteered_at" class="form-label">{{ $org->config->labelDate }} <span class="text-danger">*</span></label>
                <input type="date" name="volunteered_at" id="volunteered_at" class="form-control @error('volunteered_at') is-invalid @enderror"
                    value="{{ $selectedDate }}" max="{{ today()->toDateString() }}" required>
                @error('volunteered_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text" data-context-date-status role="status"></div>
            </div>
            <div class="{{ $events->isNotEmpty() ? 'col-md-4' : 'col-md-8' }}">
                <label for="location_id" class="form-label">{{ $org->config->labelLocation }} <span class="text-danger">*</span></label>
                <select name="location_id" id="location_id" class="form-select @error('location_id') is-invalid @enderror" required
                    data-remember-location="{{ $org->id }}" data-restore-location="{{ session()->hasOldInput('location_id') ? 'false' : 'true' }}">
                    <option value="">Choose {{ strtolower($org->config->labelLocation) }}...</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected($selectedLocationId == $location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
                @error('location_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text" data-location-status role="status"></div>
                <div class="form-text" data-event-location-status role="status"></div>
            </div>
            @if($events->isNotEmpty())
            <div class="col-md-4">
                <label for="special_event_id" class="form-label">Special event <span class="text-muted fw-normal">(optional)</span></label>
                <select name="special_event_id" id="special_event_id" class="form-select @error('special_event_id') is-invalid @enderror">
                    <option value="">No event</option>
                    @foreach(['Recent and upcoming' => $events->filter(fn ($event) => ($event->ends_on ?? $event->starts_on)->gte(today()->subDays(30)))->sortBy('starts_on'), 'Earlier events' => $events->filter(fn ($event) => ($event->ends_on ?? $event->starts_on)->lt(today()->subDays(30)))->sortByDesc('starts_on')] as $group => $groupEvents)
                        @if($groupEvents->isNotEmpty())
                            <optgroup label="{{ $group }}">
                            @foreach($groupEvents as $event)
                                <option value="{{ $event->id }}" @selected(old('special_event_id', request('event')) == $event->id) data-location-ids="{{ $event->locations->pluck('id')->join(',') }}" data-event-name="{{ $event->name }}" data-event-date="{{ $event->starts_on->toDateString() }}">{{ $event->name }} &middot; {{ $event->starts_on->format('M j, Y') }}</option>
                            @endforeach
                            </optgroup>
                        @endif
                    @endforeach
                </select>
                @error('special_event_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @endif
        </div>
        @if($events->isNotEmpty())<p class="form-text mt-n1 mb-3">Event dates and locations can be changed when needed.</p>@endif
    </div>

    <fieldset class="mb-3">
        <legend class="form-label">{{ $org->config->labelHours }} <span class="text-danger">*</span></legend>
        <div class="row g-2">
            <div class="col-6"><label for="duration_hours" class="form-label">Hours</label><input type="number" name="duration_hours" id="duration_hours" class="form-control @error('duration_hours') is-invalid @enderror" min="0" max="24" step="1" inputmode="numeric" placeholder="0" value="{{ old('duration_hours') }}" @if($org->config->showHoursHint) aria-describedby="duration-help" @endif>@error('duration_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="col-6"><label for="duration_minutes" class="form-label">Minutes</label><input type="number" name="duration_minutes" id="duration_minutes" class="form-control @error('duration_minutes') is-invalid @enderror" min="0" max="59" step="1" inputmode="numeric" placeholder="0" value="{{ old('duration_minutes') }}" @if($org->config->showHoursHint) aria-describedby="duration-help" @endif>@error('duration_minutes')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
        </div>
        @if($org->config->showHoursHint)<div id="duration-help" class="form-text">Enter a total between 1 minute and 24 hours.</div>@endif
        @error('hours')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </fieldset>

    @guest
    <div class="mb-3">
        <label for="name" class="form-label">{{ $org->config->labelName }} @if($org->config->requireName)<span class="text-danger">*</span>@else<span class="text-muted fw-normal">(optional)</span>@endif</label>
        <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
            value="{{ old('name') }}" maxlength="255" autocomplete="name" @required($org->config->requireName)>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    @endguest

    @if($org->config->showNotes)
    <details class="volunteer-notes mb-3" @if(old('notes') || $errors->has('notes')) open @endif>
        <summary>Add a note <span class="text-muted">(optional)</span></summary>
        <label for="notes" class="visually-hidden">Notes</label>
        <textarea name="notes" id="notes" class="form-control mt-2 @error('notes') is-invalid @enderror" rows="2" maxlength="1000">{{ old('notes') }}</textarea>
        @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </details>
    @endif

    <div class="d-grid">
        <button type="submit" class="btn btn-log" @disabled($locations->isEmpty())>
            {{ $org->config->submitButtonText }}
            <span class="htmx-indicator spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>
        </button>
    </div>
    @guest<p class="text-muted small text-center mt-2 mb-0">No account required.</p>@endguest
</form>
