@extends('layouts.admin')

@section('title', 'New User')

@section('content')
<div class="container-fluid" style="max-width: 760px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">New User</h1>
            <p class="text-muted mb-0">Create an admin or volunteer account.</p>
        </div>
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary btn-sm">← Users</a>
    </div>

    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf

        @include('admin.users._form', ['user' => new App\Models\User])

        <div class="d-flex justify-content-end gap-2 pb-5">
            <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary px-4">Create User</button>
        </div>
    </form>

</div>
@endsection
