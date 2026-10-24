@extends('layouts.admin')
@section('title', 'Overview')
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading"><div><p class="eyebrow">{{ $organization?->name ?? 'Platform administration' }}</p><h1>Welcome back, {{ Str::before(auth()->user()->name, ' ') }}.</h1><p class="text-muted mb-0">{{ $organization ? 'Your community is making a difference. Here is where things stand.' : 'Help organizations turn community effort into measurable impact.' }}</p></div><a class="btn btn-primary" href="{{ $organization ? route('admin.entries.create') : route('admin.organizations.create') }}">{{ $organization ? 'Add volunteer hours' : 'Create organization' }}</a></div>
    <div class="row g-3 mb-4">@include('admin.partials.stats-cards')</div>
    @if($organization)
    <section class="share-panel mb-4">
        <p class="eyebrow">Your public form</p><h2 class="h4">Make it easy to show up.</h2><p class="text-muted">Share this link anywhere your volunteers gather. No account required.</p>
        <label class="visually-hidden" for="public-url">Public logging URL</label><div class="d-flex flex-wrap gap-2"><input id="public-url" class="form-control share-url" readonly value="{{ route('org.hours.index', $organization) }}"><button class="btn btn-primary" data-copy-target="public-url" type="button">Copy link</button><a class="btn btn-outline-secondary" href="{{ route('org.hours.index', $organization) }}" target="_blank" rel="noopener">Preview form</a></div><p class="small mt-2 mb-0" data-copy-status role="status"></p>
    </section>
    <div class="row g-3 mb-4">
        @foreach([['01', 'Set the scene', 'Make the form feel like your organization. Logo, color, and a warm welcome.', 'admin.branding.edit', 'Customize form'], ['02', 'Choose your places', $locationCount.' locations set up. Add the places where your volunteers make a difference.', 'admin.locations.index', 'Manage locations'], ['03', 'Bring people together', 'Create an optional special event and share a link with the event preselected.', 'admin.special-events.index', 'Plan an event']] as [$step, $title, $description, $destination, $action])
        <div class="col-lg-4"><div class="card h-100"><div class="card-body d-flex flex-column"><p class="eyebrow">Getting started / {{ $step }}</p><h2 class="h5">{{ $title }}</h2><p class="text-muted small">{{ $description }}</p><a class="mt-auto fw-semibold" href="{{ route($destination) }}">{{ $action }} &rarr;</a></div></div></div>
        @endforeach
    </div>
    @else
    <section class="card mb-4"><div class="card-body"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h4 mb-0">Organizations</h2><a href="{{ route('admin.organizations.index') }}">Manage all</a></div>
        @forelse($organizations as $item)
            <a href="{{ route('admin.organizations.show', $item) }}" class="organization-link"><span class="fw-semibold">{{ $item->name }}</span><span class="small text-muted">{{ $item->is_active ? 'Active' : 'Inactive' }} &middot; /{{ $item->slug }}/log-hours &rarr;</span></a>
        @empty
            <div class="empty-state"><h3 class="h5">Welcome your first organization.</h3><p>Create its public form, invite an administrator, and give its volunteer community a home.</p><a class="btn btn-primary" href="{{ route('admin.organizations.create') }}">Create organization</a></div>
        @endforelse
    </div></section>
    @endif
    <section class="card"><div class="card-body"><div class="d-flex justify-content-between mb-3"><h2 class="h4">Latest contributions</h2><a href="{{ route('admin.dashboard') }}">View all hours</a></div>
        @forelse($recent as $entry)
        <div class="contribution-item"><div><a class="fw-semibold" href="{{ route('admin.entries.edit', $entry) }}">{{ $entry->name ?: 'Anonymous volunteer' }}</a><span class="small d-block text-muted">{{ $entry->location?->name }} &middot; {{ $entry->volunteered_at->format('M j, Y') }}@if($entry->specialEvent) &middot; {{ $entry->specialEvent->name }}@endif</span></div><strong class="text-nowrap">{{ number_format($entry->hours, 1) }} h</strong></div>
        @empty
        <div class="empty-state"><h3 class="h5">The first contribution is on its way.</h3><p>Once a volunteer submits hours, you'll see them here.</p></div>
        @endforelse
    </div></section>
</div>
@endsection
