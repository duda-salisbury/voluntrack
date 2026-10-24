@extends('layouts.admin')

@section('title', 'Users')

@section('content')
<div class="container-fluid" style="max-width: 1100px;">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">{{ auth()->user()->isSuperAdmin() ? 'People & access' : 'Volunteers & access' }}</h1>
            <p class="text-muted mb-0">
                @if(auth()->user()->isSuperAdmin())
                    All users across all organizations.
                @else
                    Registered volunteers and administrators in {{ $org->name ?? 'your organization' }}.
                @endif
            </p>
            <p class="small text-muted mb-0 mt-1">Named and anonymous volunteers do not need an account. Find their contributions in <a href="{{ route('admin.dashboard') }}">Volunteer hours</a>.</p>
        </div>
        <a href="{{ route('admin.users.create') }}" class="btn btn-primary">+ New User</a>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
                <div class="col-12 col-sm-6 col-md-4">
                    <label class="form-label small mb-1">Search</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="Name or email…" value="{{ request('search') }}">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label small mb-1">Role</label>
                    <select name="role" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="admin"     {{ request('role') === 'admin'     ? 'selected' : '' }}>Admin</option>
                        <option value="volunteer" {{ request('role') === 'volunteer' ? 'selected' : '' }}>Volunteer</option>
                    </select>
                </div>
                @if(auth()->user()->isSuperAdmin())
                <div class="col-6 col-md-3">
                    <label class="form-label small mb-1">Organization</label>
                    <select name="org" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="none" {{ request('org') === 'none' ? 'selected' : '' }}>— No org (super-admins)</option>
                        @foreach($organizations as $o)
                            <option value="{{ $o->id }}" {{ request('org') == $o->id ? 'selected' : '' }}>
                                {{ $o->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Table --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th>Organization</th>
                        @endif
                        <th>Role</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td class="fw-semibold">
                            {{ $user->name }}
                            @if($user->id === auth()->id())
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1" style="font-size:.65rem;">you</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $user->email }}</td>
                        @if(auth()->user()->isSuperAdmin())
                            <td class="small">
                                @if($user->organization)
                                    <a href="{{ route('admin.organizations.edit', $user->organization) }}" class="text-decoration-none">
                                        {{ $user->organization->name }}
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        @endif
                        <td>
                            @if($user->isSuperAdmin())
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Super Admin</span>
                            @elseif($user->is_admin)
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Org Admin</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Volunteer</span>
                            @endif
                        </td>
                        <td class="text-muted small text-nowrap">{{ $user->created_at->format('M j, Y') }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="m-0"
                                          onsubmit="return confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5 text-muted">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>

</div>
@endsection
