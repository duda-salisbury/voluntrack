@extends('layouts.app')
@section('title', 'My organizations')
@section('content')
<div class="container volunteer-history">
    <div class="page-heading"><div><p class="eyebrow">Your community</p><h1>My organizations</h1><p class="text-muted">Switch between your organizations without signing in again.</p></div><a class="btn btn-outline-secondary" href="{{ route('home', ['find' => 1]) }}">Find an organization</a></div>
    @forelse($memberships as $membership)
        <div class="card mb-3"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h2 class="h5 mb-1">{{ $membership->name }}</h2><span class="text-muted">{{ $membership->pivot->role === 'admin' ? 'Organization administrator' : 'Volunteer' }}{{ $membership->is_active ? '' : ' · Inactive' }}</span></div>
        @if($membership->is_active)<div class="d-flex flex-wrap gap-2"><form method="POST" action="{{ route('account.organizations.switch', $membership) }}">@csrf<button class="btn btn-primary">{{ $membership->pivot->role === 'admin' ? 'Open dashboard' : 'My hours' }}</button></form><a class="btn btn-outline-secondary" href="{{ route('org.hours.index', $membership) }}">Log hours</a></div>@endif
        </div></div>
    @empty<div class="empty-state"><h2 class="h5">No organizations yet.</h2><p>Find an organization to start logging hours.</p><a class="btn btn-primary" href="{{ route('home', ['find' => 1]) }}">Find an organization</a></div>@endforelse
    <p class="mt-4"><a href="{{ route('organization-requests.index') }}">View organization requests</a> &middot; <a href="{{ route('organization-requests.create') }}">Request organization access</a></p>
    @if(auth()->user()->isSuperAdmin())<form method="POST" action="{{ route('account.platform') }}" class="mt-4">@csrf<button class="btn btn-outline-secondary">Platform overview</button></form>@endif
</div>
@endsection
