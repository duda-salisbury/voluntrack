@extends('layouts.app')

@section('title', 'Locations')

@section('content')
<div class="container-fluid" style="max-width: 1100px;">

    {{-- Page header --}}
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Locations</h1>
            <p class="text-muted mb-0">
                Manage the locations shown on the volunteer form.
                @if(isset($org) && $org)
                    &nbsp;·&nbsp;<span class="fw-semibold">{{ $org->name }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.locations.create') }}" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="me-1" style="vertical-align:-2px;" aria-hidden="true">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                New Location
            </a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">← Dashboard</a>
        </div>
    </div>

    {{-- Flash messages --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.locations.index') }}" class="card mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-5 col-md-4">
                    <label for="search" class="form-label form-label-sm mb-1">Search</label>
                    <input
                        type="search"
                        name="search"
                        id="search"
                        class="form-control form-control-sm"
                        value="{{ request('search') }}"
                        placeholder="Filter by name…"
                    >
                </div>
                <div class="col-6 col-sm-3 col-md-2">
                    <label for="status" class="form-label form-label-sm mb-1">Status</label>
                    <select name="status" id="status" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                @if($organizations->isNotEmpty())
                <div class="col-6 col-sm-4 col-md-3">
                    <label for="org" class="form-label form-label-sm mb-1">Organization</label>
                    <select name="org" id="org" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($organizations as $organization)
                            <option value="{{ $organization->id }}" {{ request('org') == $organization->id ? 'selected' : '' }}>
                                {{ $organization->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    @if(request()->hasAny(['search', 'status', 'org']))
                        <a href="{{ route('admin.locations.index') }}" class="btn btn-sm btn-outline-secondary ms-1">Clear</a>
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th>Organization</th>
                        @endif
                        <th>Source</th>
                        <th class="text-center">Entries</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($locations as $location)
                        @include('admin.locations.partials.row', ['location' => $location])
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                No locations found.
                                <a href="{{ route('admin.locations.create') }}">Create the first one →</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($locations->hasPages())
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="text-muted small">
                Showing {{ $locations->firstItem() }}–{{ $locations->lastItem() }} of {{ $locations->total() }}
            </div>
            {{ $locations->links() }}
        </div>
        @endif
    </div>

</div>
@endsection
