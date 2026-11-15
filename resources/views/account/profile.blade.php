@extends('layouts.app')
@section('title', 'My profile')
@section('content')
<div class="container volunteer-history">
    <div class="page-heading"><div><p class="eyebrow">Personal account</p><h1>My profile</h1><p class="text-muted">Your account is shared across your organizations.</p></div><a class="btn btn-outline-secondary" href="{{ route('account.organizations') }}">My organizations</a></div>
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="row g-4"><div class="col-lg-7">
        <form class="card mb-4" method="POST" action="{{ route('account.profile.update') }}"><div class="card-body">@csrf @method('PUT')<h2 class="h5">Personal details</h2>
            <div class="mb-3"><label class="form-label" for="name">Name</label><input id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
            <div class="mb-3"><label class="form-label" for="email">Email</label><input id="email" name="email" type="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
            <div class="mb-3"><label class="form-label" for="profile-password">Current password</label><input id="profile-password" name="current_password" type="password" class="form-control" autocomplete="current-password" required></div><button class="btn btn-primary">Save profile</button>
        </div></form>
        <form id="security" class="card" method="POST" action="{{ route('account.profile.password') }}"><div class="card-body">@csrf @method('PUT')<h2 class="h5">Account security</h2>
            <div class="mb-3"><label class="form-label" for="current-password">Current password</label><input id="current-password" name="current_password" type="password" class="form-control" autocomplete="current-password" required></div>
            <div class="mb-3"><label class="form-label" for="new-password">New password</label><input id="new-password" name="password" type="password" class="form-control" autocomplete="new-password" minlength="8" required></div>
            <div class="mb-3"><label class="form-label" for="password-confirmation">Confirm password</label><input id="password-confirmation" name="password_confirmation" type="password" class="form-control" autocomplete="new-password" required></div><button class="btn btn-primary">Change password</button>
        </div></form>
    </div><div class="col-lg-5"><div class="card"><div class="card-body"><h2 class="h5">Your organizations</h2>@forelse($memberships as $membership)<div class="organization-link"><strong>{{ $membership->name }}</strong><span>{{ ucfirst($membership->pivot->role) }}</span></div>@empty<p class="text-muted">You have not joined an organization yet.</p>@endforelse</div></div></div></div>
</div>
@endsection
