@extends('layouts.admin')
@section('title', 'Accounts & access')
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading"><div><p class="eyebrow">People & access</p><h1>Accounts & access</h1><p class="text-muted">Manage organization memberships without changing personal accounts. <a href="{{ route('admin.volunteer-activity') }}">Explore volunteer activity</a>.</p></div><a href="{{ route('admin.users.create') }}" class="btn btn-primary">Add volunteer</a></div>
    @if($org)
    <details class="card mb-4"><summary class="p-3 fw-semibold">Invite an administrator</summary><div class="card-body"><form method="POST" action="{{ route('admin.organizations.administrators', $org) }}" class="row g-3">@csrf
        <div class="col-md-4"><label class="form-label" for="invite-name">Name</label><input id="invite-name" class="form-control" name="name" required value="{{ old('name') }}"></div>
        <div class="col-md-5"><label class="form-label" for="invite-email">Email</label><input id="invite-email" class="form-control" type="email" name="email" required value="{{ old('email') }}"></div>
        <div class="col-md-3 d-flex align-items-end"><button class="btn btn-primary">Send invitation</button></div>
    </form></div></details>
    @endif
    <form class="card mb-4" method="GET"><div class="card-body d-flex gap-2"><label class="visually-hidden" for="people-search">Search people</label><input id="people-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Name or email"><button class="btn btn-primary">Search</button></div></form>
    <div class="card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Person</th><th>Memberships</th><th>Actions</th></tr></thead><tbody>
    @forelse($users as $user)
        <tr><td><strong>{{ $user->name }}</strong><span class="d-block small text-muted">{{ $user->email }}</span>@if($user->isSuperAdmin())<span class="badge bg-secondary">Platform administrator</span>@endif</td><td>
        @foreach($user->organizations as $membership)
            @if($orgId === null || $membership->id === $orgId)<div>{{ $membership->name }} <span class="badge bg-primary-subtle text-primary">{{ ucfirst($membership->pivot->role) }}</span></div>@endif
        @endforeach
        </td><td>
        @foreach($user->organizations as $membership)
            @if(($orgId === null || $membership->id === $orgId) && $user->id !== auth()->id() && ($orgId === null || $membership->pivot->role !== 'admin'))
            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Remove this membership? The account and recorded hours will remain.')">@csrf @method('DELETE')<input type="hidden" name="organization_id" value="{{ $membership->id }}"><button class="btn btn-sm btn-outline-secondary">Remove from {{ $membership->name }}</button></form>
            @endif
        @endforeach
        </td></tr>
    @empty<tr><td colspan="3"><div class="empty-state">No accounts found.</div></td></tr>@endforelse
    </tbody></table></div>@if($users->hasPages())<div class="card-footer">{{ $users->links() }}</div>@endif</div>
</div>
@endsection
