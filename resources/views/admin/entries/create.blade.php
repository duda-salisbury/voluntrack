@extends('layouts.app')

@section('title', 'Add Entry')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm mb-3">← Back to Dashboard</a>
        <h1 class="h3 mb-1">Add Volunteer Entry</h1>
        <p class="text-muted">Create a new volunteer hour entry</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.entries.store') }}">
                @csrf

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
                                <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
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
                            value="{{ old('volunteered_at', date('Y-m-d')) }}"
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
                            value="{{ old('hours') }}"
                            min="0.01"
                            max="24"
                            step="0.25"
                            placeholder="4.5"
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
                            value="{{ old('name') }}"
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
                                {{ old('is_matched') ? 'checked' : '' }}
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
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Entry</button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
