@extends('layouts.app')
@section('title', 'My hours')
@section('content')
<div class="container volunteer-history">
    <div class="page-heading"><div><p class="eyebrow">Your impact</p><h1>My hours</h1><p class="text-muted">Your contributions across organizations.</p></div><a class="btn btn-primary" href="{{ route('account.organizations') }}">Log hours</a></div>
    <div class="card mb-4"><div class="card-body"><span class="text-muted">All-time hours across organizations</span><div class="fs-2 fw-bold">{{ number_format($totalHours, 2) }}</div></div></div>
    <form method="GET" class="d-flex gap-2 mb-4"><label class="visually-hidden" for="hours-org">Organization</label><select class="form-select" id="hours-org" name="organization_id"><option value="">All organizations</option>@foreach($memberships as $membership)<option value="{{ $membership->id }}" @selected(request('organization_id') == $membership->id)>{{ $membership->name }}</option>@endforeach</select><button class="btn btn-outline-secondary">Filter</button></form>
    <div class="card"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Date</th><th>Organization</th><th>Location / event</th><th>Hours</th></tr></thead><tbody>
    @forelse($entries as $entry)<tr><td>{{ $entry->volunteered_at->format('M j, Y') }}</td><td>{{ $entry->organization?->name ?? 'Organization unavailable' }}</td><td>{{ $entry->location?->name ?? 'Location unavailable' }}<span class="d-block small text-muted">{{ $entry->specialEvent?->name }}</span></td><td>{{ number_format($entry->hours, 2) }}</td></tr>@empty<tr><td colspan="4"><div class="empty-state"><h2 class="h5">No contributions in this view yet.</h2><p>Every hour makes a difference.</p><a class="btn btn-primary" href="{{ route('account.organizations') }}">Log hours</a></div></td></tr>@endforelse
    </tbody></table></div>@if($entries->hasPages())<div class="card-footer">{{ $entries->links() }}</div>@endif</div>
</div>
@endsection
