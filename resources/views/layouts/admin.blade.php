<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Overview') &mdash; Voluntrack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="admin-shell">
@php
    $adminUser = auth()->user();
    $adminOrg = $adminUser->organization;
    $navigation = [
        ['admin.overview', 'Overview', 'admin.overview'],
        ['admin.dashboard', 'Volunteer hours', 'admin.dashboard,admin.entries.*'],
        ['admin.locations.index', 'Locations', 'admin.locations.*'],
        ['admin.special-events.index', 'Special events', 'admin.special-events.*'],
        ['admin.users.index', $adminUser->isSuperAdmin() ? 'People & access' : 'Volunteers & access', 'admin.users.*'],
        ['admin.reports', 'Reports', 'admin.reports'],
    ];
    if ($adminUser->isSuperAdmin()) {
        $navigation[] = ['admin.organizations.index', 'Organizations', 'admin.organizations.*'];
    } else {
        $navigation[] = ['admin.branding.edit', 'Settings & public form', 'admin.branding.*'];
    }
@endphp
<a class="skip-link" href="#main-content">Skip to content</a>
<header class="admin-topbar">
    <div class="d-flex align-items-center gap-2">
        <button type="button" class="btn btn-outline-secondary d-md-none" data-bs-toggle="offcanvas" data-bs-target="#workspace-navigation" aria-controls="workspace-navigation" aria-label="Open navigation">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <a class="brand-mark" href="{{ route('admin.overview') }}" aria-label="Voluntrack overview"><img src="{{ asset('voluntrack.svg') }}" width="28" height="28" alt=""> <span class="d-none d-sm-inline">Voluntrack</span></a>
    </div>
    <div class="workspace-identity"><span class="eyebrow mb-0">{{ $adminUser->isSuperAdmin() ? 'Platform administrator' : 'Organization workspace' }}</span><strong class="text-truncate">{{ $adminOrg?->name ?? 'All organizations' }}</strong></div>
    <form method="POST" action="{{ route('logout') }}" class="ms-auto">@csrf<button class="btn btn-sm btn-outline-secondary">Sign out</button></form>
</header>
<div class="admin-frame">
    <aside class="offcanvas-md offcanvas-start admin-sidebar" tabindex="-1" id="workspace-navigation" aria-labelledby="navigation-title">
        <div class="offcanvas-header"><h2 class="offcanvas-title h5" id="navigation-title">Your workspace</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#workspace-navigation" aria-label="Close navigation"></button></div>
        <div class="offcanvas-body flex-column">
            <div class="sidebar-context">
                @if($adminOrg?->logo_url)<img src="{{ $adminOrg->logo_url }}" class="sidebar-logo" alt="">@endif
                <p class="eyebrow">{{ $adminUser->isSuperAdmin() ? 'Platform' : 'Your organization' }}</p><strong>{{ $adminOrg?->name ?? 'Voluntrack' }}</strong>
                <span class="d-block small text-muted mt-1">{{ $adminUser->name }}</span>
            </div>
            <nav aria-label="Main navigation" class="workspace-nav">
                @foreach($navigation as [$destination, $label, $patterns])
                    @php $active = request()->routeIs(...explode(',', $patterns)); @endphp
                    <a href="{{ route($destination) }}" class="{{ $active ? 'active' : '' }}" @if($active) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            @if($adminOrg)
                <div class="sidebar-help mt-auto"><p class="eyebrow">Built for your volunteers</p><p class="small text-muted">Keep logging simple. Share the form, and let every hour count.</p><a href="{{ route('org.hours.index', $adminOrg) }}" target="_blank" rel="noopener" class="btn btn-outline-primary w-100">Open public form &nearr;</a></div>
            @endif
        </div>
    </aside>
    <main id="main-content" class="admin-main">
        @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger" role="alert">{{ session('error') }}</div>@endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert" tabindex="-1" data-validation-summary><h2 class="h6">Please review these details</h2><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
        <div class="alert alert-danger d-none" role="alert" id="request-error" tabindex="-1"></div>
        @yield('content')
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
<script src="https://unpkg.com/htmx.org@2.0.0/dist/htmx.min.js"></script>
@stack('scripts')
</body>
</html>
