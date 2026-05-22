<!-- Main Form Card -->
<div class="card">
    <div class="card-header">
        <h2 class="h5 mb-0">Volunteer Entry</h2>
    </div>
    
    <div class="card-body">
        <form 
            method="POST" 
            action="{{ route('org.hours.store', $org) }}"
            hx-post="{{ route('org.hours.store', $org) }}"
            hx-target="#form-container"
            hx-swap="innerHTML"
            novalidate
        >
            @csrf

            <!-- Location Field -->
            <div class="mb-3">
                <label for="location_id" class="form-label fw-semibold">
                    Location <span class="text-danger">*</span>
                </label>
                <select 
                    name="location_id" 
                    id="location_id" 
                    class="form-select @error('location_id') is-invalid @enderror"
                    required
                    aria-describedby="locationHelp"
                >
                    <option value="">Select a trail location...</option>
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

            <!-- Date Field -->
            <div class="mb-3">
                <label for="volunteered_at" class="form-label fw-semibold">
                    Date <span class="text-danger">*</span>
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
                    Hours <span class="text-danger">*</span>
                </label>
                <input 
                    type="number" 
                    name="hours" 
                    id="hours" 
                    class="form-control @error('hours') is-invalid @enderror"
                    value="{{ old('hours') }}"
                    min="0.01"
                    max="24"
                    step="0.25"
                    placeholder="4.5"
                    required
                    aria-describedby="hoursHelp"
                >
                @error('hours')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="form-text" id="hoursHelp">Enter hours as a decimal (e.g., 2.5 for 2½ hours)</div>
                @enderror
            </div>

            <!-- Name Field (Optional) -->
            <div class="mb-4">
                <label for="name" class="form-label fw-semibold">
                    Your Name <span class="text-muted fw-normal">(optional)</span>
                </label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    class="form-control @error('name') is-invalid @enderror"
                    value="{{ old('name', (auth()->check() && auth()->user()->organization_id === $org->id) ? auth()->user()->name : null) }}"
                    maxlength="255"
                    placeholder="Anonymous volunteer"
                    aria-describedby="nameHelp"
                >
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @else
                    <div class="form-text" id="nameHelp">Leave blank to log hours anonymously</div>
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
                <button type="submit" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-2" style="vertical-align: -3px;">
                        <path d="M5 13L9 17L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>Submit Hours</span>
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
            Your hours help maintain and improve trail systems
        </small>
    </div>
</div>

@push('scripts')
<script>
    // Auto-focus first field on page load
    document.addEventListener('DOMContentLoaded', function() {
        const firstField = document.getElementById('location_id');
        if (firstField) {
            firstField.focus();
        }
    });

    // Re-focus on HTMX swap (when form is reloaded after success)
    document.body.addEventListener('htmx:afterSwap', function(event) {
        if (event.detail.target.id === 'form-container') {
            setTimeout(function() {
                const firstField = document.getElementById('location_id');
                if (firstField) {
                    firstField.focus();
                }
            }, 100);
        }
    });

    // Validate hours input
    const hoursInput = document.getElementById('hours');
    if (hoursInput) {
        hoursInput.addEventListener('input', function(e) {
            const value = parseFloat(e.target.value);
            if (value < 0) {
                e.target.value = 0;
            } else if (value > 24) {
                e.target.value = 24;
            }
        });
    }
</script>
@endpush
