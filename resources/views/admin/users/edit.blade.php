@extends('layouts.admin')

@section('title', 'Edit User')

@section('content')
<div class="container-fluid" style="max-width: 760px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Edit: {{ $user->name }}</h1>
            <p class="text-muted mb-0">{{ $user->email }}</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">← Users</a>
    </div>

    <form method="POST" action="{{ route('admin.users.update', $user) }}">
        @csrf
        @method('PUT')

        @include('admin.users._form')

        <div class="d-flex justify-content-end gap-2 pb-5">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Save Changes</button>
        </div>
    </form>

</div>
@endsection
