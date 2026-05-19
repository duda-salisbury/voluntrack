@extends('layouts.app')

@section('title', 'Sign In')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-sm-8 col-md-6 col-lg-5">

            <div class="text-center mb-4">
                <h1 class="h3">Volunteer Sign In</h1>
                <p class="text-muted">{{ $org->name }}</p>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-4">

                    @if(session('status'))
                        <div class="alert alert-success">{{ session('status') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('org.account.login.post', $org) }}" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control @error('email') is-invalid @enderror"
                                value="{{ old('email') }}"
                                required
                                autocomplete="email"
                                autofocus
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="form-control @error('password') is-invalid @enderror"
                                required
                                autocomplete="current-password"
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4 form-check">
                            <input type="checkbox" name="remember" id="remember" class="form-check-input" {{ old('remember') ? 'checked' : '' }}>
                            <label for="remember" class="form-check-label">Remember me</label>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Sign In</button>
                        </div>

                        <div class="text-center mt-3">
                            <a href="{{ route('account.password.request') }}" class="text-muted small">Forgot your password?</a>
                        </div>
                    </form>

                </div>
            </div>

            <p class="text-center text-muted mt-3">
                Don&rsquo;t have an account?
                <a href="{{ route('org.account.register', $org) }}">Create one</a>
            </p>

            <p class="text-center">
                <a href="{{ route('org.hours.index', $org) }}" class="text-muted small">&larr; Back to volunteer form</a>
            </p>

        </div>
    </div>
</div>
@endsection
