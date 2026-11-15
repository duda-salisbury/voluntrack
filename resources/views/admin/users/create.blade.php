@extends('layouts.admin')
@section('title', 'Add volunteer')
@section('content')
<div class="container-fluid workspace" style="max-width: 700px">
    <div class="page-heading"><div><p class="eyebrow">People & access</p><h1>Add a volunteer</h1><p class="text-muted">An existing account keeps its password and other memberships. A new account receives a password setup email.</p></div></div>
    <form method="POST" action="{{ route('admin.users.store') }}" class="card"><div class="card-body">@csrf
        @if(auth()->user()->isPlatformWorkspace())<div class="mb-3"><label class="form-label" for="organization_id">Organization</label><select class="form-select" id="organization_id" name="organization_id" required><option value="">Choose an organization</option>@foreach($organizations as $organization)<option value="{{ $organization->id }}" @selected(old('organization_id') == $organization->id)>{{ $organization->name }}</option>@endforeach</select></div>@endif
        <div class="mb-3"><label class="form-label" for="email">Email</label><input class="form-control" id="email" type="email" name="email" value="{{ old('email') }}" required></div>
        <div class="mb-3"><label class="form-label" for="name">Name <span class="text-muted">(required for a new account)</span></label><input class="form-control" id="name" name="name" value="{{ old('name') }}" maxlength="255"></div>
        <button class="btn btn-primary">Add volunteer</button><a class="btn btn-outline-secondary" href="{{ route('admin.users.index') }}">Cancel</a>
    </div></form>
</div>
@endsection
