@extends('layouts.admin')
@section('title', 'Special Events')
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading">
        <div><p class="eyebrow">Bring your community together</p><h1>Special events</h1><p class="text-muted mb-0">Group contributions without losing their location or date.</p></div>
        <a class="btn btn-primary" href="{{ route('admin.special-events.create', request()->only('organization_id')) }}">Create special event</a>
    </div>
    <div class="row g-3">
        @forelse($events as $event)
        <div class="col-md-6 col-xl-4"><article class="card h-100"><div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between gap-2 mb-3">
                <span class="eyebrow mb-0">{{ $event->starts_on->format('M j, Y') }}@if($event->ends_on) &ndash; {{ $event->ends_on->format('M j, Y') }}@endif</span>
                <span class="badge {{ $event->is_active ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $event->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            <h2 class="h4"><a href="{{ route('admin.special-events.show', $event) }}">{{ $event->name }}</a></h2>
            @if(auth()->user()->isSuperAdmin())<p class="small text-muted">{{ $event->organization?->name ?? 'Archived organization' }}</p>@endif
            <p class="text-muted">{{ Str::limit($event->description, 140) }}</p>
            <div class="mt-auto pt-3 border-top d-flex gap-4"><div><strong class="fs-4">{{ number_format($event->volunteer_entries_sum_hours ?? 0, 1) }}</strong><span class="small text-muted d-block">hours contributed</span></div><div><strong class="fs-4">{{ $event->volunteer_entries_count }}</strong><span class="small text-muted d-block">submissions</span></div></div>
        </div></article></div>
        @empty
        <div class="col-12"><div class="empty-state"><p class="eyebrow">Room for something special</p><h2 class="h4">Your next great volunteer day starts here.</h2><p>Create a cleanup, work party, or community event. Everyday volunteering never needs an event.</p><a class="btn btn-primary" href="{{ route('admin.special-events.create') }}">Create your first event</a></div></div>
        @endforelse
    </div>
    <div class="mt-4">{{ $events->links() }}</div>
</div>
@endsection
