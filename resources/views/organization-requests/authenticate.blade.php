@extends('layouts.app')
@section('title', 'Continue your request')
@section('content')
<div class="container" style="max-width: 580px;">
    <h1 class="h2">Continue your request</h1>
    <p class="text-muted">Sign in with your existing Voluntrack account, or create one.@if(session()->has('organization_request_draft')) Your request details are saved for this session.@endif</p>
    <div class="card"><div class="card-body d-flex flex-wrap gap-2">
        <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
        <a href="{{ route('organization-requests.register') }}" class="btn btn-outline-primary">Create an account</a>
        <a href="{{ route('organization-requests.create') }}" class="btn btn-link">Back to request</a>
    </div></div>
</div>
@endsection
