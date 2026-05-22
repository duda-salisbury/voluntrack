<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Track your volunteer hours">

    <title>@yield('title', $org->name ?? 'Voluntrack') — {{ $org->name ?? 'Voluntrack' }}</title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    @if(isset($org) && $org && $org->accent_color !== '#0d6efd')
    {{-- Override Bootstrap's primary colour using per-component CSS custom properties.
         No Sass compilation required — pure runtime theming. --}}
    <style>
        :root {
            --org-accent:     {{ $org->accent_color }};
            --org-accent-rgb: {{ $org->accent_color_rgb }};
            --bs-primary:     {{ $org->accent_color }};
            --bs-primary-rgb: {{ $org->accent_color_rgb }};
            --bs-link-color:       {{ $org->accent_color }};
            --bs-link-hover-color: color-mix(in srgb, {{ $org->accent_color }} 80%, black);
        }
        .btn-primary {
            --bs-btn-bg:                  {{ $org->accent_color }};
            --bs-btn-border-color:        {{ $org->accent_color }};
            --bs-btn-hover-bg:            color-mix(in srgb, {{ $org->accent_color }} 85%, black);
            --bs-btn-hover-border-color:  color-mix(in srgb, {{ $org->accent_color }} 85%, black);
            --bs-btn-active-bg:           color-mix(in srgb, {{ $org->accent_color }} 75%, black);
            --bs-btn-active-border-color: color-mix(in srgb, {{ $org->accent_color }} 75%, black);
            --bs-btn-disabled-bg:         {{ $org->accent_color }};
            --bs-btn-disabled-border-color: {{ $org->accent_color }};
        }
        .btn-outline-primary {
            --bs-btn-color:              {{ $org->accent_color }};
            --bs-btn-border-color:       {{ $org->accent_color }};
            --bs-btn-hover-bg:           {{ $org->accent_color }};
            --bs-btn-hover-border-color: {{ $org->accent_color }};
            --bs-btn-active-bg:          {{ $org->accent_color }};
        }
        .text-primary  { color: {{ $org->accent_color }} !important; }
        .border-primary { border-color: {{ $org->accent_color }} !important; }
        .bg-primary    { background-color: {{ $org->accent_color }} !important; }
    </style>
    @endif

    @stack('styles')
</head>
<body>
    <nav class="navbar bg-body-tertiary border-bottom mb-4">
        <div class="container">
            <a href="{{ isset($org) && $org ? route('org.hours.index', $org) : route('home') }}" class="navbar-brand fw-bold d-flex align-items-center gap-2">
                @if(isset($org) && $org?->logo_url)
                    <img src="{{ $org->logo_url }}" alt="{{ $org->name }} logo"
                         style="height:32px;width:auto;object-fit:contain;">
                @else
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                @endif
                {{ isset($org) && $org ? $org->name : 'Voluntrack' }}
            </a>
            <div class="d-flex align-items-center gap-2">
                @auth
                    @if(auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary">Admin</a>
                    @elseif(isset($org) && $org && auth()->user()->organization_id === $org->id)
                        <a href="{{ route('org.account.history', $org) }}" class="btn btn-sm btn-outline-secondary">My History</a>
                        <form method="POST" action="{{ route('org.account.logout', $org) }}" class="m-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Sign Out</button>
                        </form>
                    @endif
                @else
                    @if(isset($org) && $org)
                        <a href="{{ route('org.account.login', $org) }}" class="btn btn-sm btn-outline-primary">Sign In</a>
                        <a href="{{ route('org.account.register', $org) }}" class="btn btn-sm btn-primary">Register</a>
                    @endif
                @endauth
                @stack('header-actions')
            </div>
        </div>
    </nav>

    @if(session('success'))
        <div class="container mb-3">
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="container mb-3">
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    <footer class="border-top mt-5 py-3 text-center text-muted">
        <div class="container">
            <small>&copy; {{ date('Y') }} {{ isset($org) && $org ? $org->name : 'Voluntrack' }}. Built for volunteers.</small>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    <!-- HTMX 2.0 -->
    <script src="https://unpkg.com/htmx.org@2.0.0" integrity="sha384-wS5l5IKJBvK6sPTKa2WZ1js3d947pvWXbPJ1OmWfEuxLgeHcEbjUUA5i9V5ZkpCw" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
