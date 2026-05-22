@extends('layouts.admin')

@section('title', 'New Location')

@section('content')
<div class="container py-4" style="max-width: 680px;">

    <div class="mb-4">
        <a href="{{ route('admin.locations.index') }}" class="text-muted small">&larr; Back to Locations</a>
        <h1 class="h3 mt-2 mb-0">New Location</h1>
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

    <div class="card shadow-sm">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.locations.store') }}" novalidate>
                @csrf
                @include('admin.locations._form', ['location' => null])
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Location</button>
                    <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
