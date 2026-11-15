@extends('layouts.admin')
@section('title', 'Volunteer activity')
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading"><div><p class="eyebrow">{{ $org?->name ?? 'Platform' }} / Participation</p><h1>Volunteer activity</h1><p class="text-muted mb-0">Explore contributions without assuming people with the same name are the same volunteer.</p></div><a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Manage accounts & access</a></div>
    @include('admin.partials.report-filters', ['filterRoute' => 'admin.volunteer-activity'])
    <section class="card mb-4"><div class="card-body"><div class="section-heading"><div><h2 class="h4">Account-linked contributions</h2><p class="text-muted small mb-0">Totals and latest work date reflect the selected filters. Accounts are identified by their account, not their submitted name.</p></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Volunteer account</th><th>Last volunteered</th><th>Submissions</th><th>Hours</th><th><span class="visually-hidden">History</span></th></tr></thead><tbody>
        @forelse($accounts as $account)
            <tr><td><strong>{{ $account->volunteerUser?->name ?? 'Unavailable account #'.$account->volunteer_user_id }}</strong>@if($account->volunteerUser)<span class="d-block small text-muted">{{ $account->volunteerUser->email }}</span>@endif</td><td>{{ \Carbon\Carbon::parse($account->last_worked)->format('M j, Y') }}</td><td>{{ number_format($account->submissions) }}</td><td>{{ number_format($account->total, 2) }}</td><td>@if($account->volunteerUser)<a class="btn btn-sm btn-outline-primary" href="{{ route('admin.dashboard', array_merge(request()->except(['page', 'volunteer']), ['volunteer_user_id' => $account->volunteer_user_id])) }}">View history</a>@endif</td></tr>
        @empty
            <tr><td colspan="5"><div class="empty-state"><h3 class="h5">No account-linked contributions in this view.</h3><p class="mb-0">Volunteers can always contribute without an account.</p></div></td></tr>
        @endforelse
        </tbody></table></div>
        @if($accounts->hasPages())<div class="mt-3">{{ $accounts->links() }}</div>@endif
    </div></section>
    <section class="share-panel"><h2 class="h5">Contributions without an account</h2><p class="text-muted">{{ number_format($unlinked) }} submissions in this view are not linked to an account. Similar names or browser identifiers do not establish a reliable identity. Search individual records to review or correct them.</p><a class="btn btn-outline-primary" href="{{ route('admin.dashboard', request()->except('page')) }}">Explore all contribution records</a></section>
</div>
@endsection
