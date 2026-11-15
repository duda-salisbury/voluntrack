@extends('layouts.admin')
@section('title', 'Organizations')
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading"><div><p class="eyebrow">Platform / Organizations</p><h1>Organizations</h1><p class="text-muted mb-0">Find organizations and manage their access.</p></div><div class="d-flex gap-2 flex-wrap"><a href="{{ route('admin.organization-requests.index') }}" class="btn btn-outline-secondary">Organization requests</a><a href="{{ route('admin.organizations.create') }}" class="btn btn-primary">Create organization</a></div></div>
    <form method="GET" action="{{ route('admin.organizations.index') }}" class="card mb-4"><div class="card-body"><div class="row g-3 align-items-end">
        <div class="col-md-6"><label for="org-name" class="form-label">Organization name</label><input id="org-name" type="search" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by name"></div>
        <div class="col-md-3"><label for="org-status" class="form-label">Status</label><select id="org-status" name="status" class="form-select"><option value="">All statuses</option><option value="active" @selected(request('status') === 'active')>Active</option><option value="inactive" @selected(request('status') === 'inactive')>Inactive</option></select></div>
        <div class="col-md-auto d-flex gap-2"><button class="btn btn-primary">Filter</button><a href="{{ route('admin.organizations.index') }}" class="btn btn-outline-secondary">Clear</a></div>
    </div></div></form>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Organization</th><th>Status</th><th>Administrators</th><th>Locations</th><th>Submissions</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
        <tbody>
        @forelse($organizations as $organization)
            <tr>
                <td><a class="fw-semibold" href="{{ route('admin.organizations.show', $organization) }}">{{ $organization->name }}</a><span class="d-block text-muted small">/{{ $organization->slug }}</span></td>
                <td><span class="badge {{ $organization->is_active ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-secondary-subtle text-secondary border border-secondary-subtle' }}">{{ $organization->is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>{{ number_format($organization->administrators_count) }} @if($organization->administrators_count === 0)<span class="badge bg-warning-subtle text-dark">Needs access</span>@endif</td>
                <td>{{ number_format($organization->locations_count) }}</td><td>{{ number_format($organization->volunteer_entries_count) }}</td>
                <td><div class="d-flex flex-wrap gap-2"><form method="POST" action="{{ route('account.organizations.workspace', $organization) }}">@csrf<button class="btn btn-sm btn-primary">Open Workspace</button></form><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.organizations.show', $organization) }}">Details & access</a></div></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty-state"><h2 class="h5">No organizations found.</h2><p>Try a different search or create an organization.</p><a href="{{ route('admin.organizations.create') }}" class="btn btn-primary">Create organization</a></div></td></tr>
        @endforelse
        </tbody>
    </table></div>@if($organizations->hasPages())<div class="card-footer">{{ $organizations->links() }}</div>@endif</div>
</div>
@endsection
