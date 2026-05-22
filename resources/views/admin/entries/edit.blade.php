@extends('layouts.admin')

@section('title', 'Edit Entry')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm mb-3">← Back to Dashboard</a>
        <h1 class="h3 mb-1">Edit Volunteer Entry</h1>
        <p class="text-muted">Update entry details</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.entries.update', $entry) }}">
                @csrf
                @method('PUT')

                <div class="row">
                    <!-- Location -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="location_id" class="form-label fw-semibold">
                            Location <span class="text-danger">*</span>
                        </label>
                        <select 
                            name="location_id" 
                            id="location_id" 
                            class="form-select @error('location_id') is-invalid @enderror"
                            required
                        >
                            <option value="">Select a location...</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}" {{ old('location_id', $entry->location_id) == $location->id ? 'selected' : '' }}>
                                    {{ $location->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Date -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="volunteered_at" class="form-label fw-semibold">
                            Date <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="volunteered_at" 
                            id="volunteered_at" 
                            class="form-control @error('volunteered_at') is-invalid @enderror"
                            value="{{ old('volunteered_at', $entry->volunteered_at->format('Y-m-d')) }}"
                            required
                        >
                        @error('volunteered_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Hours -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="hours" class="form-label fw-semibold">
                            Hours <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="hours" 
                            id="hours" 
                            class="form-control @error('hours') is-invalid @enderror"
                            value="{{ old('hours', $entry->hours) }}"
                            min="0.01"
                            max="24"
                            step="any"
                            required
                        >
                        @error('hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Name -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="name" class="form-label fw-semibold">
                            Volunteer Name <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $entry->name) }}"
                            maxlength="255"
                            placeholder="Leave blank for anonymous"
                        >
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Matched Checkbox -->
                    <div class="col-12 mb-4">
                        <div class="form-check">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                name="is_matched" 
                                id="is_matched"
                                value="1"
                                {{ old('is_matched', $entry->is_matched) ? 'checked' : '' }}
                            >
                            <label class="form-check-label fw-semibold" for="is_matched">Mark as Matched Hours</label>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="col-12 mb-4">
                        <label for="notes" class="form-label fw-semibold">
                            Notes <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <textarea 
                            name="notes" 
                            id="notes" 
                            class="form-control @error('notes') is-invalid @enderror"
                            rows="4"
                            placeholder="Add any additional notes..."
                        >{{ old('notes', $entry->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Metadata -->
                    <div class="col-12 mb-4">
                        <div class="card bg-light border-0">
                            <div class="card-body py-2">
                                <small class="text-muted">
                                    <strong>Source:</strong> {{ ucfirst($entry->source) }}
                                    @if($entry->matched_by_admin_id)
                                        <span class="mx-2">•</span>
                                        <strong>Matched by:</strong> {{ $entry->matchedByAdmin->name }}
                                    @endif
                                    <span class="mx-2">•</span>
                                    <strong>Created:</strong> {{ $entry->created_at->format('M j, Y g:i A') }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update Entry</button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
