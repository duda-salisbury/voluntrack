@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid" style="max-width: 1400px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Admin Dashboard</h1>
            <p class="text-muted mb-0">Manage volunteer hour entries</p>
        </div>
        <div class="d-flex gap-3">
            <a href="{{ route('admin.entries.create') }}" class="btn btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Add Entry
            </a>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Entries</div>
                    <div class="fs-2 fw-bold">{{ number_format($stats['total_entries']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small mb-1">Total Hours</div>
                    <div class="fs-2 fw-bold text-success">{{ number_format($stats['total_hours'], 1) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small mb-1">Matched Hours</div>
                    <div class="fs-2 fw-bold text-primary">{{ number_format($stats['matched_hours'], 1) }}</div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6 col-lg-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted small mb-1">Unmatched Hours</div>
                    <div class="fs-2 fw-bold text-warning">{{ number_format($stats['unmatched_hours'], 1) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-3">
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="location_id" class="form-label">Location</label>
                    <select name="location_id" id="location_id" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="matched" class="form-label">Matched Status</label>
                    <select name="matched" id="matched" class="form-select">
                        <option value="">All</option>
                        <option value="yes" {{ request('matched') === 'yes' ? 'selected' : '' }}>Matched</option>
                        <option value="no" {{ request('matched') === 'no' ? 'selected' : '' }}>Unmatched</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Entries Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Hours</th>
                        <th>Matched</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        @include('admin.partials.entry-row', ['entry' => $entry])
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">No entries found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($entries->hasPages())
            <div class="card-footer">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection


