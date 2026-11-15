@extends('layouts.app')
@section('title', 'Create an account')
@section('content')
<div class="container" style="max-width: 580px;">
    <h1 class="h2">Create an account</h1>
    <p class="text-muted">Already have a Voluntrack account? <a href="{{ route('login') }}">Sign in instead</a>.</p>
    @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('organization-requests.register.store') }}" class="card">
        @csrf
        <div class="card-body">
            <div class="mb-3"><label for="name" class="form-label fw-semibold">Full name</label><input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $draft['contact_name'] ?? '') }}" maxlength="255" autocomplete="name" required>@error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label for="email" class="form-label fw-semibold">Email</label><input type="email" id="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $draft['contact_email'] ?? '') }}" maxlength="255" autocomplete="email" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-3"><label for="password" class="form-label fw-semibold">Password</label><input type="password" id="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password" minlength="8" required>@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
            <div class="mb-4"><label for="password-confirmation" class="form-label fw-semibold">Confirm password</label><input type="password" id="password-confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required></div>
            <button class="btn btn-primary" type="submit">Create account</button>
        </div>
    </form>
</div>
@endsection
