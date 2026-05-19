@extends('layouts.app')

@section('title', 'Edit Location')

@section('content')
<div class="container py-4" style="max-width: 680px;">

    <div class="mb-4">
        <a href="{{ route('admin.locations.index') }}" class="text-muted small">&larr; Back to Locations</a>
        <h1 class="h3 mt-2 mb-1">Edit Location</h1>
        <p class="text-muted mb-0">{{ $location->name }}</p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.locations.update', $location) }}" novalidate>
                @csrf
                @method('PUT')
                @include('admin.locations._form')
                <div class="d-flex gap-2 flex-wrap">
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                    <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Danger zone --}}
    @if($location->volunteerEntries()->doesntExist())
    <div class="card border-danger mt-4">
        <div class="card-body">
            <h6 class="text-danger mb-1">Delete Location</h6>
            <p class="text-muted small mb-3">
                This location has no entries and can be permanently deleted.
            </p>
            <form method="POST" action="{{ route('admin.locations.destroy', $location) }}"
                  onsubmit="return confirm('Permanently delete this location? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete Location</button>
            </form>
        </div>
    </div>
    @endif

</div>
@endsection
