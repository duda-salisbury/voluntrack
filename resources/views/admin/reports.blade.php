@extends('layouts.admin')
@section('title', 'Impact Reports')
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading"><div><p class="eyebrow">{{ $org?->name ?? 'Across the platform' }} / Community impact</p><h1>Small efforts. Real impact.</h1><p class="text-muted mb-0">A clear picture of the time your community gives.</p></div><a class="btn btn-outline-secondary" href="{{ route('admin.dashboard', request()->query()) }}">View these submissions</a></div>
    @include('admin.partials.report-filters', ['filterRoute' => 'admin.reports'])
    <div class="row g-3 mb-4">@include('admin.partials.stats-cards')</div>
    <div class="row g-4 mb-4">
        <div class="col-lg-7"><section class="card h-100"><div class="card-body">
            <p class="eyebrow">Hours over time</p><h2 class="h4">A growing contribution</h2>
            @forelse($byMonth as $month => $hours)
                <div class="metric-bar-row"><div class="d-flex justify-content-between gap-3 small mb-1"><span>{{ \Carbon\Carbon::parse($month.'-01')->format('M Y') }}</span><strong>{{ number_format($hours, 1) }} h</strong></div><div class="metric-bar" aria-hidden="true"><span style="width:{{ $byMonth->max() > 0 ? $hours / $byMonth->max() * 100 : 0 }}%"></span></div></div>
            @empty
                <div class="empty-state"><h3 class="h6">Your impact story starts with the first hour.</h3><p>Share your form or adjust the filters to see contributions here.</p></div>
            @endforelse
        </div></section></div>
        <div class="col-lg-5"><section class="card h-100"><div class="card-body">
            <p class="eyebrow">People behind the hours</p><h2 class="h4">Estimated unique volunteers</h2><div class="display-4 fw-semibold mb-2">{{ number_format($stats['estimated_volunteers']) }}</div>
            <p class="text-muted small">An estimate, not a headcount: accounts, anonymous browser identifiers, and names are counted within each organization. Shared devices or names may combine people; different devices or signing in later may count someone twice.</p>
            <p class="small mb-0"><strong>{{ number_format($stats['unidentified_entries']) }} submissions</strong> have no usable identifier and are excluded from this estimate.</p>
            <hr><p class="small mb-0"><strong>{{ number_format($stats['event_hours'], 1) }} hours</strong> are attributed to special events. These are already included in total hours, not added on top. Matching hours are an independent administrative classification.</p>
        </div></section></div>
    </div>
    <div class="row g-4 mb-4">
        @foreach([['Hours by location', $byLocation, 'location'], ['Hours by special event', $byEvent, 'specialEvent']] as [$heading, $rows, $relation])
        <div class="col-lg-6"><section class="card h-100"><div class="card-body"><h2 class="h4 mb-3">{{ $heading }}</h2>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th scope="col">{{ $relation === 'location' ? 'Location' : 'Event' }}</th><th scope="col" class="text-end">Submissions</th><th scope="col" class="text-end">Hours</th></tr></thead><tbody>
                @forelse($rows as $row)
                    <tr><th scope="row" class="fw-normal">{{ $row->$relation?->name ?? ($relation === 'specialEvent' ? 'Ordinary volunteering (no event)' : 'Archived location') }}</th><td class="text-end">{{ number_format($row->submissions) }}</td><td class="text-end fw-semibold">{{ number_format($row->total, 1) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-muted py-4">No contributions match these filters.</td></tr>
                @endforelse
            </tbody></table></div>
        </div></section></div>
        @endforeach
    </div>
    <section class="card"><div class="card-body"><h2 class="h4 mb-3">Recent submissions</h2>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th scope="col">Received</th><th scope="col">Volunteer</th><th scope="col">Location / event</th><th scope="col" class="text-end">Hours</th></tr></thead><tbody>
            @forelse($recent as $entry)
            <tr><td class="small text-nowrap">{{ $entry->created_at->format('M j, Y') }}<span class="d-block text-muted">Worked {{ $entry->volunteered_at->format('M j') }}</span></td><td><a href="{{ route('admin.entries.edit', $entry) }}">{{ $entry->name ?: 'Anonymous' }}</a>@if(auth()->user()->isSuperAdmin())<small class="d-block text-muted">{{ $entry->organization?->name }}</small>@endif</td><td>{{ $entry->location?->name }}<span class="d-block small text-muted">{{ $entry->specialEvent?->name ?? 'Ordinary volunteering' }}</span></td><td class="text-end fw-semibold">{{ number_format($entry->hours, 2) }}</td></tr>
            @empty
            <tr><td colspan="4" class="text-muted py-4">No submissions yet.</td></tr>
            @endforelse
        </tbody></table></div>
    </div></section>
</div>
@endsection
