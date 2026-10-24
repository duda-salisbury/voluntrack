@extends('layouts.admin')
@section('title', 'Volunteer Hours')
@section('content')
<div class="container-fluid workspace" id="hours-workspace">
    <div class="page-heading">
        <div><p class="eyebrow">{{ $org?->name ?? 'All organizations' }} / Contributions</p><h1>Volunteer hours</h1><p class="text-muted mb-0">Every contribution, in one place. Filter, review, and keep the details accurate.</p></div>
        <a href="{{ route('admin.entries.create') }}" class="btn btn-primary">Add hours</a>
    </div>
    <div class="row g-3 mb-4" id="stats-cards"
         hx-get="{{ route('admin.stats', request()->query()) }}" hx-trigger="statsUpdated from:body" hx-swap="innerHTML">
        @include('admin.partials.stats-cards')
    </div>
    @include('admin.partials.report-filters', ['filterRoute' => 'admin.dashboard'])
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"><p class="small text-muted mb-0">{{ number_format($entries->total()) }} submissions in this view</p><a href="{{ route('admin.reports', request()->query()) }}" class="btn btn-sm btn-outline-secondary">Explore impact report</a></div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden">Volunteer submissions matching the selected filters</caption>
                <thead><tr><th scope="col">Date</th><th scope="col">Volunteer</th>@if(auth()->user()->isSuperAdmin())<th scope="col">Organization</th>@endif<th scope="col">Location / event</th><th scope="col">Hours</th><th scope="col">Matching</th><th scope="col">Notes</th><th scope="col">Actions</th></tr></thead>
                <tbody>
                    @forelse($entries as $entry)
                        @include('admin.partials.entry-row')
                    @empty
                        <tr><td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}"><div class="empty-state"><h2 class="h5">No hours in this view yet.</h2><p>Try a different date range, clear the filters, or share your public form to welcome the first contribution.</p><a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Clear filters</a></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($entries->hasPages())<div class="card-footer">{{ $entries->links() }}</div>@endif
    </div>
</div>
@endsection
