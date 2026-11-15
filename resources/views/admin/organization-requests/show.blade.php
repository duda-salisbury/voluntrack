@extends('layouts.admin')
@section('title', 'Review organization request')
@section('content')
<div class="container-fluid workspace">
    <div class="page-heading"><div><p class="eyebrow"><a href="{{ route('admin.organization-requests.index') }}">Organization requests</a> / Review</p><h1>{{ $organizationRequest->organization_name }}</h1><p class="text-muted mb-0">{{ $organizationRequest->type === 'new' ? 'New organization' : 'Existing organization access' }} &middot; {{ ucfirst($organizationRequest->status) }}</p></div></div>
    <div class="row g-4">
        <div class="col-lg-7">
            <section class="card mb-4"><div class="card-body">
                <h2 class="h5">Request details</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4">Account</dt><dd class="col-sm-8">{{ $organizationRequest->requester?->name ?? 'Account unavailable' }} @if($organizationRequest->requester)({{ $organizationRequest->requester->email }})@endif</dd>
                    <dt class="col-sm-4">Contact</dt><dd class="col-sm-8">{{ $organizationRequest->contact_name }} ({{ $organizationRequest->contact_email }})</dd>
                    <dt class="col-sm-4">Website</dt><dd class="col-sm-8">@if($organizationRequest->website)<a href="{{ $organizationRequest->website }}" target="_blank" rel="noopener noreferrer">{{ $organizationRequest->website }}</a>@else Not provided @endif</dd>
                    <dt class="col-sm-4">Submitted</dt><dd class="col-sm-8">{{ $organizationRequest->created_at->format('M j, Y g:i A') }}</dd>
                    <dt class="col-sm-4">Description</dt><dd class="col-sm-8" style="white-space: pre-wrap;">{{ $organizationRequest->description ?: 'Not provided' }}</dd>
                    @if($organizationRequest->type === 'existing')<dt class="col-sm-4">Requested organization</dt><dd class="col-sm-8">@if($organizationRequest->existingOrganization && ! $organizationRequest->existingOrganization->trashed())<a href="{{ route('admin.organizations.show', $organizationRequest->existingOrganization) }}">{{ $organizationRequest->existingOrganization->name }}</a>@else Unavailable @endif</dd>@endif
                </dl>
            </div></section>
            @if($matches->isNotEmpty() || $pendingMatches->isNotEmpty())
                <section class="alert alert-warning"><h2 class="h5">Potential matches</h2><p class="small">Names or website domains may match. Verify these records before making a decision; no access is granted automatically.</p>
                    @if($matches->isNotEmpty())<h3 class="h6">Existing organizations</h3><ul>@foreach($matches as $match)<li>@if(! $match->trashed())<a href="{{ route('admin.organizations.show', $match) }}">{{ $match->name }}</a>@else{{ $match->name }} (archived)@endif{{ ! $match->is_active ? ' (inactive)' : '' }}</li>@endforeach</ul>@endif
                    @if($pendingMatches->isNotEmpty())<h3 class="h6">Other pending requests</h3><ul class="mb-0">@foreach($pendingMatches as $match)<li><a href="{{ route('admin.organization-requests.show', $match) }}">{{ $match->organization_name }}</a> &middot; {{ $match->created_at->format('M j, Y') }}</li>@endforeach</ul>@endif
                </section>
            @endif
        </div>
        <div class="col-lg-5">
            <section class="card"><div class="card-body">
                @if($organizationRequest->status === 'pending')
                    <h2 class="h5">Make a decision</h2>
                    @if($organizationRequest->type === 'existing')<p class="small text-muted">Verify the requester's authority with the organization before granting administrator access.</p>@else<p class="small text-muted">Approval creates an organization and gives the requester administrator access. Matching existing organizations must be resolved first.</p>@endif
                    <form method="POST" action="{{ route('admin.organization-requests.decide', $organizationRequest) }}">
                        @csrf
                        <div class="mb-3"><label for="review-notes" class="form-label">Internal notes <span class="text-muted">(optional; private)</span></label><textarea id="review-notes" name="review_notes" class="form-control" rows="3" maxlength="2000">{{ old('review_notes') }}</textarea></div>
                        <div class="mb-3"><label for="decline-reason" class="form-label">Decline reason <span class="text-muted">(optional; shared with requester)</span></label><textarea id="decline-reason" name="decline_reason" class="form-control" rows="2" maxlength="1000">{{ old('decline_reason') }}</textarea></div>
                        @if($organizationRequest->type === 'existing')<div class="form-check mb-3"><input type="checkbox" class="form-check-input" id="verified" name="verified" value="1" @checked(old('verified'))><label class="form-check-label" for="verified">I verified that the requester is authorized to administer this organization.</label></div>@endif
                        <div class="d-flex flex-wrap gap-2"><button type="submit" name="decision" value="approved" class="btn btn-primary">Approve request</button><button type="submit" name="decision" value="declined" class="btn btn-outline-danger">Decline request</button></div>
                    </form>
                @else
                    <h2 class="h5">{{ ucfirst($organizationRequest->status) }}</h2>
                    <p class="small text-muted">{{ $organizationRequest->decided_at?->format('M j, Y g:i A') }} @if($organizationRequest->reviewer) &middot; {{ $organizationRequest->reviewer->name }} @endif</p>
                    @if($organizationRequest->review_notes)<p><strong>Internal notes</strong><br>{{ $organizationRequest->review_notes }}</p>@endif
                    @if($organizationRequest->decline_reason)<p><strong>Reason shared with requester</strong><br>{{ $organizationRequest->decline_reason }}</p>@endif
                    @if($organizationRequest->organization && ! $organizationRequest->organization->trashed())<p><a href="{{ route('admin.organizations.show', $organizationRequest->organization) }}">View organization</a></p>@endif
                    <form method="POST" action="{{ route('admin.organization-requests.resend', $organizationRequest) }}">@csrf<button class="btn btn-outline-secondary btn-sm">Resend decision email</button></form>
                @endif
            </div></section>
        </div>
    </div>
</div>
@endsection
