<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Find your organization and log your volunteer hours.">
    <title>Voluntrack — Volunteer Hour Tracking</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <script src="https://unpkg.com/htmx.org@2.0.0/dist/htmx.min.js" defer></script>

    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .hero {
            padding: 5rem 1rem 3rem;
            text-align: center;
        }

        .hero-logo {
            width: 52px;
            height: 52px;
            color: #0d6efd;
            margin-bottom: 1.25rem;
        }

        .hero-title {
            font-size: clamp(2rem, 5vw, 3.25rem);
            font-weight: 800;
            letter-spacing: -.02em;
            line-height: 1.1;
            color: #212529;
            margin-bottom: .75rem;
        }

        .hero-sub {
            font-size: 1.125rem;
            color: #6c757d;
            max-width: 480px;
            margin: 0 auto 2.5rem;
        }

        .search-wrap {
            max-width: 520px;
            margin: 0 auto;
            position: relative;
        }

        .search-wrap .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #adb5bd;
            pointer-events: none;
        }

        .search-wrap input {
            padding-left: 2.75rem;
            height: 52px;
            font-size: 1rem;
            border-radius: 12px;
            border: 1.5px solid #dee2e6;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            transition: border-color .15s, box-shadow .15s;
        }

        .search-wrap input:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,.15);
            outline: none;
        }

        .results-section {
            max-width: 640px;
            margin: 2.5rem auto 0;
            padding: 0 1rem;
        }

        .org-card {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.25rem;
            background: #fff;
            border: 1.5px solid #e9ecef;
            border-radius: 12px;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s, box-shadow .15s, transform .1s;
        }

        .org-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 16px rgba(13,110,253,.1);
            transform: translateY(-1px);
            color: inherit;
        }

        .org-swatch {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: #fff;
        }

        .org-swatch img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            border-radius: 10px;
        }

        .org-name {
            font-weight: 600;
            font-size: 1rem;
            color: #212529;
            line-height: 1.2;
        }

        .org-slug {
            font-size: .8rem;
            color: #adb5bd;
            font-family: monospace;
        }

        .org-arrow {
            margin-left: auto;
            color: #ced4da;
            flex-shrink: 0;
            transition: color .15s, transform .15s;
        }

        .org-card:hover .org-arrow {
            color: #0d6efd;
            transform: translateX(3px);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: #adb5bd;
        }

        .results-label {
            font-size: .8rem;
            font-weight: 600;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: #adb5bd;
            margin-bottom: .75rem;
        }

        .admin-link {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            font-size: .8rem;
            color: #ced4da;
            text-decoration: none;
        }

        .admin-link:hover { color: #6c757d; }
    </style>
</head>
<body>

<div class="hero">
    <svg class="hero-logo" viewBox="0 0 24 24" fill="none" aria-hidden="true">
        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>

    <h1 class="hero-title">Log your volunteer hours.</h1>
    <p class="hero-sub">Find your organization below to get started.</p>

    <div class="search-wrap">
        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
            <path d="M21 21l-4.35-4.35" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <input
            type="search"
            name="q"
            id="org-search"
            class="form-control"
            placeholder="Search organizations…"
            value="{{ $search }}"
            autocomplete="off"
            hx-get="{{ route('home') }}"
            hx-trigger="input changed delay:200ms, search"
            hx-target="#org-results"
            hx-select="#org-results"
            hx-push-url="false"
            aria-label="Search organizations"
            autofocus
        >
    </div>
</div>

<div id="org-results" class="results-section">
    @if($search)
        <div class="results-label">
            {{ $organizations->count() }} result{{ $organizations->count() === 1 ? '' : 's' }} for &ldquo;{{ $search }}&rdquo;
        </div>
    @elseif($organizations->isNotEmpty())
        <div class="results-label">All Organizations</div>
    @endif

    @forelse($organizations as $org)
        @php
            $initials = collect(explode(' ', $org->name))
                ->map(fn($w) => strtoupper($w[0] ?? ''))
                ->take(2)
                ->implode('');
        @endphp
        <a href="{{ route('org.hours.index', $org) }}" class="org-card mb-3 d-flex">
            <div class="org-swatch" style="background-color: {{ $org->accent_color }};">
                @if($org->logo_url)
                    <img src="{{ $org->logo_url }}" alt="{{ $org->name }}">
                @else
                    {{ $initials }}
                @endif
            </div>
            <div>
                <div class="org-name">{{ $org->name }}</div>
                <div class="org-slug">/{{ $org->slug }}</div>
            </div>
            <svg class="org-arrow" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </a>
    @empty
        <div class="empty-state">
            @if($search)
                <p class="mb-0">No organizations match &ldquo;{{ $search }}&rdquo;</p>
            @else
                <p class="mb-0">No active organizations yet.</p>
            @endif
        </div>
    @endforelse
</div>

@auth
    @if(auth()->user()->is_admin)
        <a href="{{ route('admin.dashboard') }}" class="admin-link">Admin ↗</a>
    @endif
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
