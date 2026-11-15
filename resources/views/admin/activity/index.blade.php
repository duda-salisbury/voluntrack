@extends('layouts.admin')
@section('title', 'Activity')
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading"><div><p class="eyebrow">{{ $platform ? 'Platform' : 'People & access' }}</p><h1>Activity</h1><p class="text-muted mb-0">{{ $platform ? 'Account access and activity across Voluntrack.' : 'Recent activity in '.$organization->name.'.' }}</p></div></div>
    <div class="row g-3 mb-4">
        @foreach($stats as $label => $count)
        <div class="col-sm-4"><div class="card h-100"><div class="card-body"><div class="text-muted small">{{ $label }} &middot; last 30 days</div><strong class="fs-3">{{ number_format($count) }}</strong></div></div></div>
        @endforeach
    </div>
    <form method="GET" class="card mb-4"><div class="card-body">
        <div class="row g-2">
            <div class="col-md-4"><label class="form-label" for="activity-search">Search actions</label><input id="activity-search" class="form-control" name="search" value="{{ request('search') }}" placeholder="Search activity"></div>
            <div class="col-md-4"><label class="form-label" for="activity-user">User</label><input id="activity-user" class="form-control" name="user" value="{{ request('user') }}" placeholder="Actor or affected person"></div>
            <div class="col-md-4"><label class="form-label" for="activity-category">Category</label><select id="activity-category" class="form-select" name="category"><option value="">All categories</option>@foreach($categories as $value => $label)<option value="{{ $value }}" @selected(request('category') === $value)>{{ $label }}</option>@endforeach</select></div>
            @if($platform)
            <div class="col-md-4"><label class="form-label" for="activity-org">Organization</label><select id="activity-org" class="form-select" name="organization_id"><option value="">All organizations</option>@foreach($organizations as $item)<option value="{{ $item->id }}" @selected(request('organization_id') == $item->id)>{{ $item->name }}{{ $item->trashed() ? ' (archived)' : '' }}</option>@endforeach</select></div>
            @endif
            <div class="col-md-3"><label class="form-label" for="activity-from">From</label><input id="activity-from" type="date" class="form-control" name="date_from" value="{{ request('date_from') }}"></div>
            <div class="col-md-3"><label class="form-label" for="activity-to">To</label><input id="activity-to" type="date" class="form-control" name="date_to" value="{{ request('date_to') }}"></div>
            <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Filter</button></div>
        </div>
    </div></form>
    <div class="card">
        <div class="table-responsive"><table class="table align-middle mb-0">
            <thead><tr><th>Activity</th><th>Actor</th>@if($platform)<th>Organization</th>@endif<th>When</th></tr></thead>
            <tbody>
            @forelse($events as $event)
            <tr>
                <td><strong>{{ $event->description }}</strong><span class="badge bg-primary-subtle text-primary ms-1">{{ $categories[$event->category] ?? 'Activity' }}</span>
                    @if(($event->subjectUser && $event->subject_user_id !== $event->actor_id) || !empty($event->metadata['changes']) || ($platform && $event->ip_address && $event->created_at->isAfter(now()->subDays(30))))
                    <details class="small mt-1"><summary>Details</summary>
                        @if($event->subjectUser && $event->subject_user_id !== $event->actor_id)<div>Affected person: {{ $event->subjectUser->name }}</div>@endif
                        @if(!empty($event->metadata['changes']))<div>Changed: {{ implode(', ', $event->metadata['changes']) }}</div>@endif
                        @if($platform && $event->ip_address && $event->created_at->isAfter(now()->subDays(30)))<div>IP address: {{ $event->ip_address }}</div>@endif
                    </details>
                    @endif
                </td>
                <td>{{ $event->actor?->name ?? ($event->category === 'authentication' ? 'Unknown' : 'Anonymous / system') }}</td>
                @if($platform)<td>{{ $event->organization?->name ?? 'Platform-wide' }}</td>@endif
                <td><time datetime="{{ $event->created_at->toIso8601String() }}">{{ $event->created_at->format('M j, Y g:i A') }}</time></td>
            </tr>
            @empty
            <tr><td colspan="{{ $platform ? 4 : 3 }}"><div class="empty-state"><strong>No activity found.</strong><p>Activity appears here as people use and manage Voluntrack. Try adjusting your filters.</p></div></td></tr>
            @endforelse
            </tbody>
        </table></div>
        @if($events->hasPages())<div class="card-footer">{{ $events->links() }}</div>@endif
    </div>
</div>
@endsection
