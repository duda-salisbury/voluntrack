@extends('layouts.app')
@section('title', 'My organization requests')
@section('content')
<div class="container" style="max-width: 900px;">
    <div class="page-heading"><div><p class="eyebrow">Your account</p><h1>My organization requests</h1><p class="text-muted mb-0">Track requests for your organizations.</p></div><a class="btn btn-outline-primary" href="{{ route('organization-requests.create') }}">New request</a></div>
    @forelse($requests as $item)
        <article class="card mb-3"><div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-2"><div><h2 class="h5 mb-1">{{ $item->organization_name }}</h2><p class="small text-muted mb-2">{{ $item->type === 'new' ? 'New organization' : 'Existing organization access' }} &middot; Submitted {{ $item->created_at->format('M j, Y') }}</p></div><div><span class="badge {{ $item->status === 'approved' ? 'bg-success-subtle text-success' : ($item->status === 'declined' ? 'bg-secondary-subtle text-secondary' : 'bg-warning-subtle text-dark') }}">{{ ucfirst($item->status) }}</span></div></div>
            @if($item->status === 'pending')<p class="mb-0">Your request was received and is awaiting review.</p>@endif
            @if($item->status === 'declined')<p class="mb-0">Your request was declined. @if($item->decline_reason){{ $item->decline_reason }}@endif</p>@endif
            @if($item->status === 'approved')
                <p class="mb-2">Your request was approved.</p>
                @if($item->organization && ! $item->organization->trashed() && $item->organization->is_active && auth()->user()->hasOrganizationRole($item->organization->id, 'admin'))
                    <form method="POST" action="{{ route('account.organizations.switch', $item->organization) }}">@csrf<button class="btn btn-primary btn-sm">Open organization setup</button></form>
                @else
                    <p class="small text-muted mb-0">This organization workspace is currently unavailable. Contact a platform administrator.</p>
                @endif
            @endif
        </div></article>
    @empty
        <div class="empty-state"><h2 class="h5">No requests yet</h2><p>Request a new organization or administrator access to an existing one.</p><a href="{{ route('organization-requests.create') }}" class="btn btn-primary">Get started</a></div>
    @endforelse
    {{ $requests->links() }}
</div>
@endsection
