@extends('layouts.admin')
@section('title', 'Overview')
@section('content')
<div class="container-fluid workspace">
    @if($organization)
        @unless($organization->is_active)
            <div class="alert alert-warning">This organization is inactive. Its public form and ordinary administrator access are unavailable until it is activated in platform settings.</div>
        @endunless
        <div class="page-heading">
            <div><p class="eyebrow">{{ $organization->name }} / Overview</p><h1>Welcome back, {{ Str::before(auth()->user()->name, ' ') }}.</h1><p class="text-muted mb-0">Here's how your community is showing up.</p></div>
            <div class="d-flex flex-wrap gap-2"><a class="btn btn-primary" href="{{ route('admin.entries.create') }}">Add individual hours</a><a class="btn btn-outline-primary" href="{{ route('admin.entries.group') }}">Add multiple volunteers</a><a class="btn btn-outline-primary" href="{{ route('admin.entries.group-total') }}">Add group total</a><a class="btn btn-outline-primary" href="#share-form">Share volunteer form</a></div>
        </div>
        <div class="section-heading"><div><p class="eyebrow mb-1">Your community</p><h2 class="h4">The last 30 days</h2><p class="small text-muted">Hours and submissions received in the last 30 days.</p></div><a href="{{ route('admin.reports') }}">Explore reports &rarr;</a></div>
        <div class="row g-3 mb-4">
            <div class="col-sm-6"><div class="card metric-card"><div class="card-body"><span class="text-muted small">Hours submitted</span><div class="fs-2 fw-bold">{{ number_format($recentHours, 1) }}</div></div></div></div>
            <div class="col-sm-6"><div class="card metric-card"><div class="card-body"><span class="text-muted small">Submissions received</span><div class="fs-2 fw-bold">{{ number_format($recentEntries) }}</div></div></div></div>
        </div>
        @if($locationCount === 0)
            <section class="setup-notice mb-4"><div><p class="eyebrow mb-1">Getting ready</p><h2 class="h5">Prepare your volunteer form</h2><p class="text-muted mb-2">Review your details, add an active location, and share your form.</p><div class="d-flex flex-wrap gap-3 small"><a href="{{ route('admin.branding.edit') }}">Review organization details</a><a href="#share-form">Share your form</a><a href="{{ route('admin.special-events.create') }}">Create an optional event</a><a href="{{ route('admin.users.index') }}">Invite an administrator</a></div></div><a href="{{ route('admin.locations.create') }}" class="btn btn-primary">Add a location</a></section>
        @endif
        <section id="share-form" class="share-panel mb-4">
            <p class="eyebrow">Your public form</p><h2 class="h4">Make it easy to show up.</h2><p class="text-muted">Share this link anywhere your volunteers gather. No account required.</p>
            <label class="visually-hidden" for="public-url">Public logging URL</label><div class="d-flex flex-wrap gap-2"><input id="public-url" class="form-control share-url" readonly value="{{ route('org.hours.index', $organization) }}"><button class="btn btn-primary" data-copy-target="public-url" type="button">Copy link</button><a class="btn btn-outline-secondary" href="{{ route('org.hours.index', $organization) }}" target="_blank" rel="noopener">Preview form</a></div><p class="small mt-2 mb-0" data-copy-status role="status"></p>
            <details class="share-qr mt-3" data-qr-source="public-url"><summary class="fw-semibold">Show QR code</summary><p class="small text-muted mt-2">Scan to open your volunteer form. Generated in your browser.</p><div data-qr-image role="img" aria-label="QR code for the volunteer logging form"></div><p class="small text-muted" data-qr-status role="status"></p><noscript>Enable JavaScript to display the QR code, or share the link above.</noscript></details>
        </section>
        <section class="mb-4"><div class="section-heading"><h2 class="h4">Your workspace</h2></div>
            <div class="quick-links">
                <a href="{{ route('admin.locations.index') }}"><strong>Locations</strong><span>Manage places to volunteer &rarr;</span></a>
                <a href="{{ route('admin.special-events.index') }}"><strong>Special events</strong><span>Bring your community together &rarr;</span></a>
                <a href="{{ route('admin.volunteer-activity') }}"><strong>Volunteer activity</strong><span>Explore contributions &rarr;</span></a>
                <a href="{{ route('admin.reports') }}"><strong>Reports</strong><span>Explore your impact &rarr;</span></a>
            </div>
        </section>
    @else
        <div class="page-heading"><div><p class="eyebrow">Platform administration</p><h1>Platform overview</h1><p class="text-muted mb-0">Help organizations turn community effort into lasting impact.</p></div><a class="btn btn-primary" href="{{ route('admin.organizations.create') }}">Create organization</a></div>
        @if($pendingRequests > 0)<div class="alert alert-info"><a href="{{ route('admin.organization-requests.index', ['status' => 'pending']) }}" class="alert-link">{{ number_format($pendingRequests) }} organization {{ Str::plural('request', $pendingRequests) }}</a> awaiting review.</div>@endif
        <div class="row g-3 mb-4">
            @foreach([['Organizations', $platformStats['organizations']], ['Active organizations', $platformStats['active']], ['Without administrators', $platformStats['without_admins']], ['Submissions / last 30 days', $recentEntries]] as [$label, $value])
                <div class="col-6 col-xl-3"><div class="card metric-card h-100"><div class="card-body"><span class="text-muted small">{{ $label }}</span><div class="fs-2 fw-bold">{{ number_format($value) }}</div></div></div></div>
            @endforeach
        </div>
        <div class="quick-links mb-4">
            <a href="{{ route('admin.organizations.index') }}"><strong>Organizations</strong><span>Manage organizations &rarr;</span></a>
            <a href="{{ route('admin.users.index') }}"><strong>People & access</strong><span>Manage platform access &rarr;</span></a>
            <a href="{{ route('admin.reports') }}"><strong>Reporting</strong><span>Explore platform activity &rarr;</span></a>
        </div>
        <section class="card mb-4"><div class="card-body"><div class="section-heading"><div><h2 class="h4 mb-1">Needs attention</h2><p class="text-muted small mb-0">Organizations without an assigned administrator.</p></div><a href="{{ route('admin.organizations.index') }}">Manage organizations &rarr;</a></div>
            @forelse($attention as $item)
                <a href="{{ route('admin.organizations.show', $item) }}" class="organization-link"><span class="fw-semibold">{{ $item->name }}</span><span class="small text-muted">{{ $item->is_active ? 'Active' : 'Inactive' }} &middot; Assign administrator &rarr;</span></a>
            @empty
                <div class="empty-state"><h3 class="h5">All caught up.</h3><p class="mb-0">Every organization has an administrator, or there are no organizations yet.</p></div>
            @endforelse
            @if($platformStats['without_admins'] > 5)<p class="small text-muted mt-3 mb-0">Showing 5 of {{ number_format($platformStats['without_admins']) }} organizations without administrators.</p>@endif
        </div></section>
    @endif
    <section class="card"><div class="card-body"><div class="section-heading"><div><h2 class="h4 mb-1">Recent activity</h2><p class="small text-muted mb-0">Latest submissions received in the last 30 days{{ $organization ? '' : ' across the platform' }}.</p></div><a href="{{ route('admin.dashboard') }}">View volunteer hours &rarr;</a></div>
        @forelse($recent as $entry)
            <div class="contribution-item"><div><a class="fw-semibold" href="{{ route('admin.entries.edit', $entry) }}">{{ $entry->is_group_total ? 'Group total' : ($entry->name ?: 'Anonymous volunteer') }}</a><span class="small d-block text-muted">@unless($organization){{ $entry->organization?->name ?? 'Organization unavailable' }} &middot; @endunless{{ $entry->location?->name ?? 'Location unavailable' }} &middot; volunteered {{ $entry->volunteered_at->format('M j, Y') }}@if($entry->specialEvent) &middot; {{ $entry->specialEvent->name }}@endif</span></div><strong class="text-nowrap">{{ number_format($entry->hours, 1) }} h</strong></div>
        @empty
            <div class="empty-state"><h3 class="h5">No recent submissions yet.</h3><p class="mb-0">New contributions will appear here as they come in.</p></div>
        @endforelse
    </div></section>
</div>
@endsection
