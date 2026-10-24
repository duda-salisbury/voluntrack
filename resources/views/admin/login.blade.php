@extends('layouts.app')

@section('title', 'Admin Login')

@section('content')
<div class="container" style="max-width: 480px;">
    <div class="text-center mb-4">
        <h1 class="h3 mb-1">Admin Login</h1>
        <p class="text-muted">Administrator access only</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('login.post') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">
                        Email <span class="text-danger">*</span>
                    </label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                    >
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">
                        Password <span class="text-danger">*</span>
                    </label>
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

                <div class="mb-3">
                    <div class="form-check">
                        <input 
                            class="form-check-input" 
                            type="checkbox" 
                            name="remember" 
                            id="remember"
                            {{ old('remember') ? 'checked' : '' }}
                        >
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                </div>

                <p class="small text-end"><a href="{{ route('account.password.request') }}">Forgot your password?</a></p>

                <!-- Submit Button -->
                <div class="d-grid gap-3">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Sign In
                    </button>
                    
                    <a href="{{ route('home') }}" class="btn btn-secondary">
                        Back to Public Form
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
