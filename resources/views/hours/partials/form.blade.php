<!-- Main Form Card -->
<div class="card">
    <div class="card-header">
        <h2 class="h5 mb-0">Your contribution</h2>
    </div>
    
    <div class="card-body">
        <form 
            method="POST" 
            action="{{ route('org.hours.store', $org) }}"
            hx-post="{{ route('org.hours.store', $org) }}"
            hx-target="#form-container"
            hx-select="#form-container"
            hx-swap="outerHTML"
            hx-disabled-elt="find button[type='submit']"
        >
            @csrf
            <input type="hidden" name="anon_token" value="{{ old('anon_token') }}" data-anon-token>
            @if($errors->any())
                <div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary><h3 class="h6">A few details need your attention</h3><ul class="mb-0">@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>
            @endif
            @if($locations->isEmpty())
                <div class="alert alert-info">This organization is getting ready to welcome volunteers. Check back once a location has been added.</div>
            @endif

            <!-- Location Field -->
            <div class="mb-3">
                <label for="location_id" class="form-label fw-semibold">
                    {{ $org->config->labelLocation }} <span class="text-danger">*</span>
                </label>
                <select 
                    name="location_id" 
                    id="location_id" 
                    class="form-select @error('location_id') is-invalid @enderror"
                    required
                    aria-describedby="locationHelp"
                >
                    <option value="">Choose {{ strtolower($org->config->labelLocation) }}...</option>
                    @foreach($locations as $location)
                        <option 
                            value="{{ $location->id }}" 
                            {{ old('location_id') == $location->id ? 'selected' : '' }}
                        >
                            {{ $location->name }}
                        </option>
                    @endforeach
                </select>
                @error('location_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="form-text" id="locationHelp">Choose where you volunteered</div>
                @enderror
            </div>

            @if($events->isNotEmpty())
            <div class="mb-3">
                <label for="special_event_id" class="form-label fw-semibold">Special event <span class="text-muted fw-normal">(optional)</span></label>
                <select name="special_event_id" id="special_event_id" class="form-select @error('special_event_id') is-invalid @enderror" aria-describedby="event-help">
                    <option value="">Everyday volunteering / no event</option>
                    @foreach($events as $event)
                    <option value="{{ $event->id }}" @selected(old('special_event_id', request('event')) == $event->id)>{{ $event->name }} &middot; {{ $event->starts_on->format('M j, Y') }}</option>
                    @endforeach
                </select>
                <div id="event-help" class="form-text">Taking part in an organized event? Select it here. Otherwise, leave this blank.</div>
                @error('special_event_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @endif

            <!-- Date Field -->
            <div class="mb-3">
                <label for="volunteered_at" class="form-label fw-semibold">
                    {{ $org->config->labelDate }} <span class="text-danger">*</span>
                </label>
                <input 
                    type="date" 
                    name="volunteered_at" 
                    id="volunteered_at" 
                    class="form-control @error('volunteered_at') is-invalid @enderror"
                    value="{{ old('volunteered_at', date('Y-m-d')) }}"
                    max="{{ date('Y-m-d') }}"
                    required
                    aria-describedby="dateHelp"
                >
                @error('volunteered_at')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="form-text" id="dateHelp">When did you volunteer?</div>
                @enderror
            </div>

            <!-- Hours Field -->
            <div class="mb-3">
                <label for="hours" class="form-label fw-semibold">
                    {{ $org->config->labelHours }} <span class="text-danger">*</span>
                </label>
                <input 
                    type="number" 
                    name="hours" 
                    id="hours" 
                    class="form-control @error('hours') is-invalid @enderror"
                    value="{{ old('hours') }}"
                    min="0.01"
                    max="24"
                    step="0.01"
                    inputmode="decimal"
                    placeholder="4.5"
                    required
                    @if($org->config->showHoursHint) aria-describedby="hoursHelp" @endif
                >
                @error('hours')
                    <div class="invalid-feedback">{{ $message }}</div>
                @elseif($org->config->showHoursHint)
                    <div class="form-text" id="hoursHelp">Enter hours as a decimal (e.g., 2.5 for 2½ hours)</div>
                @enderror
            </div>

            <!-- Name Field (Optional) -->
            <div class="mb-4">
                <label for="name" class="form-label fw-semibold">
                    {{ $org->config->labelName }} @if($org->config->requireName)<span class="text-danger">*</span>@else<span class="text-muted fw-normal">(optional)</span>@endif
                </label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', (auth()->check() && auth()->user()->isVolunteer() && auth()->user()->organization_id === $org->id) ? auth()->user()->name : null) }}"
                    maxlength="255"
                    autocomplete="name"
                    @required($org->config->requireName)
                    placeholder="Anonymous volunteer"
                    aria-describedby="nameHelp"
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="form-text" id="nameHelp">{{ $org->config->requireName ? 'Your organization asks for a name. Creating an account is still optional.' : 'Leave blank to log hours anonymously. No account required.' }}</div>
                @enderror
            </div>

            @if($org->config->showNotes)
            <!-- Notes Field -->
            <div class="mb-3">
                <label for="notes" class="form-label fw-semibold">
                    Notes <span class="text-muted fw-normal">(optional)</span>
                </label>
                <textarea
                    name="notes"
                    id="notes"
                    class="form-control @error('notes') is-invalid @enderror"
                    rows="3"
                    maxlength="1000"
                    placeholder="Any additional details about your volunteer work..."
                >{{ old('notes') }}</textarea>
                @error('notes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            @endif

            <!-- Submit Button -->
            <div class="d-grid">
                <button type="submit" class="btn btn-primary" @disabled($locations->isEmpty())>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2" style="vertical-align: -3px;">
                        <path d="M5 13L9 17L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>{{ $org->config->submitButtonText }}</span>
                    <span class="htmx-indicator spinner-border spinner-border-sm ms-2" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>

    <div class="card-footer text-center">
        <small class="text-muted">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-1" style="vertical-align: -2px;">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                    <path d="M12 6V12L16 14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
            Thank you for showing up for your community.
        </small>
    </div>
</div>
