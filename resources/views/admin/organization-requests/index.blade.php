@extends('layouts.admin')
@section('title', 'Organization requests')
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading"><div><p class="eyebrow">Platform / Organizations</p><h1>Organization requests</h1><p class="text-muted mb-0">{{ number_format($pendingCount) }} awaiting review.</p></div><a href="{{ route('admin.organizations.index') }}" class="btn btn-outline-secondary">Organizations</a></div>
    <form method="GET" class="card mb-4"><div class="card-body"><div class="row g-3 align-items-end">
        <div class="col-md-6"><label for="request-search" class="form-label">Organization or contact</label><input id="request-search" name="search" type="search" class="form-control" value="{{ request('search') }}"></div>
        <div class="col-md-3"><label for="request-status" class="form-label">Status</label><select id="request-status" name="status" class="form-select"><option value="">All statuses</option>@foreach(['pending', 'approved', 'declined'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
        <div class="col-md-auto"><button class="btn btn-primary">Filter</button> <a class="btn btn-outline-secondary" href="{{ route('admin.organization-requests.index') }}">Clear</a></div>
    </div></div></form>
    <div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th>Organization</th><th>Requester</th><th>Website</th><th>Submitted</th><th>Status</th><th><span class="visually-hidden">Actions</span></th></tr></thead>
        <tbody>
        @forelse($requests as $item)
            <tr>
                <td><a class="fw-semibold" href="{{ route('admin.organization-requests.show', $item) }}">{{ $item->organization_name }}</a><span class="d-block small text-muted">{{ $item->type === 'new' ? 'New organization' : 'Existing access' }}</span></td>
                <td>{{ $item->requester?->name ?? 'Account unavailable' }}<span class="d-block small text-muted">{{ $item->requester?->email }}</span></td>
                <td>@if($item->website)<a href="{{ $item->website }}" target="_blank" rel="noopener noreferrer">{{ $item->website_domain }}</a>@else<span class="text-muted">Not provided</span>@endif</td>
                <td class="text-nowrap">{{ $item->created_at->format('M j, Y') }}</td>
                <td><span class="badge {{ $item->status === 'approved' ? 'bg-success-subtle text-success' : ($item->status === 'declined' ? 'bg-secondary-subtle text-secondary' : 'bg-warning-subtle text-dark') }}">{{ ucfirst($item->status) }}</span></td>
                <td><a href="{{ route('admin.organization-requests.show', $item) }}" class="btn btn-sm btn-outline-primary">Review</a></td>
            </tr>
        @empty
            <tr><td colspan="6"><div class="empty-state"><h2 class="h5">No requests found</h2><p class="mb-0">New requests will appear here for review.</p></div></td></tr>
        @endforelse
        </tbody>
    </table></div>@if($requests->hasPages())<div class="card-footer">{{ $requests->links() }}</div>@endif</div>
</div>
@endsection
