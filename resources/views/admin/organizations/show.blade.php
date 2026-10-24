@extends('layouts.admin')
@section('title', $organization->name)
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading">
        <div>
            <p class="eyebrow">Organization overview</p>
            <h1>{{ $organization->name }}</h1>
            <p class="text-muted mb-0">A home for your community's contribution.</p>
        </div>
        <a href="{{ route('admin.organizations.edit', $organization) }}" class="btn btn-outline-secondary">Settings &amp; branding</a>
    </div>
    @unless($organization->is_active)
        <div class="alert alert-warning">This organization is inactive. Its public form and administrator access are unavailable until you activate it in settings.</div>
    @endunless
    <section class="share-panel mb-4" aria-labelledby="share-title">
        <div><p class="eyebrow">Ready to share</p><h2 id="share-title" class="h5">Your volunteer logging link</h2><p class="small text-muted">No account required. Share in a newsletter, on your website, or with your crew.</p></div>
        <label for="public-url" class="visually-hidden">Public logging URL</label>
        <div class="d-flex flex-wrap gap-2">
            <input id="public-url" class="form-control share-url" readonly value="{{ route('org.hours.index', $organization) }}">
            <button class="btn btn-primary" type="button" data-copy-target="public-url">Copy link</button>
            <a href="{{ route('org.hours.index', $organization) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Preview form</a>
        </div>
        <p class="small mt-2 mb-0" data-copy-status role="status"></p>
    </section>
    <div class="row g-4">
        <section class="col-lg-5">
            <div class="card h-100"><div class="card-body">
                <p class="eyebrow">Make it yours</p><h2 class="h4">Your next steps</h2>
                <ol class="onboarding-list">
                    <li><a href="#administrators">Invite an administrator</a><span>They choose their own password through a secure email link.</span></li>
                    <li><a href="{{ route('admin.locations.index', ['org' => $organization->id]) }}">Set up locations</a><span>{{ $organization->locations_count }} locations ready for volunteer work.</span></li>
                    <li><a href="{{ route('admin.organizations.edit', $organization) }}">Personalize the public form</a><span>Logo, color, welcome message, and field labels.</span></li>
                    <li><a href="{{ route('admin.special-events.index', ['organization_id' => $organization->id]) }}">Plan a special event</a><span>Optional events bring related contributions together.</span></li>
                    <li><a href="{{ route('admin.reports', ['organization_id' => $organization->id]) }}">See your impact</a><span>{{ $organization->volunteer_entries_count }} submissions recorded.</span></li>
                </ol>
            </div></div>
        </section>
        <section class="col-lg-7" id="administrators">
            <div class="card"><div class="card-body">
                <p class="eyebrow">Organization access</p><h2 class="h4">Administrators</h2>
                @forelse($administrators as $administrator)
                    <div class="border-bottom py-3 d-flex justify-content-between gap-2 flex-wrap">
                        <div><strong>{{ $administrator->name }}</strong><div class="small text-muted">{{ $administrator->email }}</div></div>
                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.users.edit', $administrator) }}">Manage account</a>
                    </div>
                @empty
                    <p class="text-muted">Invite the first administrator to welcome {{ $organization->name }} to Voluntrack.</p>
                @endforelse
                <h3 class="h6 mt-4">Invite or assign an administrator</h3>
                <p class="small text-muted">New accounts receive a password-setup email. An existing member of this organization can be promoted; repeat an invitation here to send a fresh link. Accounts belonging to another organization are never moved.</p>
                <form method="POST" action="{{ route('admin.organizations.administrators', $organization) }}" class="row g-3">
                    @csrf
                    <div class="col-sm-6"><label class="form-label" for="invite-name">Name</label><input id="invite-name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-sm-6"><label class="form-label" for="invite-email">Email</label><input id="invite-email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                    <div class="col-12"><button class="btn btn-primary">Send secure invitation</button></div>
                </form>
            </div></div>
        </section>
    </div>
</div>
@endsection
