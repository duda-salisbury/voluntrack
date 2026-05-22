<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — Voluntrack Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <style>
        body { background-color: #f8f9fa; }
        .admin-sidebar {
            width: 220px;
            min-height: 100vh;
            background: #fff;
            border-right: 1px solid #dee2e6;
            flex-shrink: 0;
        }
        .admin-sidebar .nav-link {
            color: #495057;
            border-radius: 6px;
            padding: .45rem .75rem;
            font-size: .9rem;
        }
        .admin-sidebar .nav-link:hover { background: #f1f3f5; color: #212529; }
        .admin-sidebar .nav-link.active { background: #e7f1ff; color: #0d6efd; font-weight: 600; }
        .admin-sidebar .nav-section {
            font-size: .7rem;
            font-weight: 700;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: #adb5bd;
            padding: .5rem .75rem .25rem;
            margin-top: .5rem;
        }
        .admin-main { min-width: 0; flex: 1; }
    </style>

    @stack('styles')
</head>
<body>

{{-- Top bar --}}
<nav class="navbar bg-white border-bottom px-3" style="min-height:56px;">
    <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ route('admin.dashboard') }}">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        Voluntrack
    </a>

    @php $adminUser = auth()->user(); @endphp

    <div class="d-flex align-items-center gap-3 ms-auto">
        {{-- Org context pill --}}
        @if($adminUser->isSuperAdmin())
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle d-none d-sm-inline-flex align-items-center gap-1">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Super Admin
            </span>
        @elseif($adminUser->organization)
            @php $adminOrg = $adminUser->organization; @endphp
            <span class="d-none d-sm-inline-flex align-items-center gap-2 border rounded px-2 py-1" style="font-size:.8rem; border-color: {{ $adminOrg->accent_color }}33 !important;">
                <span class="rounded-circle d-inline-block flex-shrink-0"
                      style="width:10px;height:10px;background:{{ $adminOrg->accent_color }};"></span>
                <span class="fw-semibold text-dark" style="line-height:1;">{{ $adminOrg->name }}</span>
                <span class="text-muted" style="line-height:1;">Org Admin</span>
            </span>
        @endif

        {{-- User name --}}
        <span class="text-muted small d-none d-md-inline">{{ $adminUser->name }}</span>

        <form method="POST" action="{{ route('logout') }}" class="m-0">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">Logout</button>
        </form>
    </div>
</nav>

<div class="d-flex">

    {{-- Sidebar --}}
    <aside class="admin-sidebar d-none d-md-flex flex-column py-3 px-2">

        {{-- Org context block for org-admins --}}
        @if(!auth()->user()->isSuperAdmin() && auth()->user()->organization)
            @php $adminOrg = auth()->user()->organization; @endphp
            <div class="mx-1 mb-2 px-2 py-2 rounded" style="background:{{ $adminOrg->accent_color }}12; border:1px solid {{ $adminOrg->accent_color }}33;">
                <div class="d-flex align-items-center gap-2">
                    @if($adminOrg->logo_url)
                        <img src="{{ $adminOrg->logo_url }}" alt="" style="height:22px;width:auto;object-fit:contain;border-radius:4px;">
                    @else
                        <span class="rounded d-inline-block flex-shrink-0"
                              style="width:22px;height:22px;background:{{ $adminOrg->accent_color }};"></span>
                    @endif
                    <span class="fw-semibold small text-dark" style="line-height:1.2;">{{ $adminOrg->name }}</span>
                </div>
                @if($adminOrg->is_active)
                    <a href="{{ route('org.hours.index', $adminOrg) }}" target="_blank"
                       class="d-block mt-1 text-muted" style="font-size:.72rem;">
                        /{{ $adminOrg->slug }} ↗
                    </a>
                @endif
            </div>
        @endif

        <div class="nav-section">Entries</div>
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            Dashboard
        </a>
        <a href="{{ route('admin.entries.create') }}"
           class="nav-link {{ request()->routeIs('admin.entries.create') ? 'active' : '' }}">
            Add Entry
        </a>

        <div class="nav-section">Manage</div>
        <a href="{{ route('admin.locations.index') }}"
           class="nav-link {{ request()->routeIs('admin.locations*') ? 'active' : '' }}">
            Locations
        </a>
        <a href="{{ route('admin.users.index') }}"
           class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
            Users
        </a>

        @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('admin.organizations.index') }}"
               class="nav-link {{ request()->routeIs('admin.organizations*') ? 'active' : '' }}">
                Organizations
            </a>
            <a href="{{ route('admin.organizations.create') }}"
               class="nav-link {{ request()->routeIs('admin.organizations.create') ? 'active' : '' }}">
                + New Organization
            </a>
        @endif

        @if(isset($org) && $org)
            <div class="nav-section">Settings</div>
            <a href="{{ route('admin.branding.edit') }}"
               class="nav-link {{ request()->routeIs('admin.branding*') ? 'active' : '' }}">
                Branding
            </a>
        @endif

    </aside>

    {{-- Mobile top nav (visible on small screens) --}}
    <div class="d-md-none w-100 bg-white border-bottom px-3 py-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm {{ request()->routeIs('admin.dashboard') ? 'btn-primary' : 'btn-outline-secondary' }}">Dashboard</a>
            <a href="{{ route('admin.locations.index') }}" class="btn btn-sm {{ request()->routeIs('admin.locations*') ? 'btn-primary' : 'btn-outline-secondary' }}">Locations</a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm {{ request()->routeIs('admin.users*') ? 'btn-primary' : 'btn-outline-secondary' }}">Users</a>
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.organizations.index') }}" class="btn btn-sm {{ request()->routeIs('admin.organizations*') ? 'btn-primary' : 'btn-outline-secondary' }}">Organizations</a>
                <a href="{{ route('admin.organizations.create') }}" class="btn btn-sm {{ request()->routeIs('admin.organizations.create') ? 'btn-primary' : 'btn-outline-secondary' }}">+ New Org</a>
            @endif
            @if(isset($org) && $org)
                <a href="{{ route('admin.branding.edit') }}" class="btn btn-sm {{ request()->routeIs('admin.branding*') ? 'btn-primary' : 'btn-outline-secondary' }}">Branding</a>
            @endif
        </div>
    </div>

    {{-- Main content --}}
    <main class="admin-main p-4">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @yield('content')
    </main>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="https://unpkg.com/htmx.org@2.0.0/dist/htmx.min.js"></script>
@stack('scripts')
</body>
</html>
