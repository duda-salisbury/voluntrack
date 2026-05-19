@extends('layouts.app')

@section('title', 'Forgot Password')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-sm-8 col-md-6 col-lg-5">

            <div class="text-center mb-4">
                <h1 class="h3">Reset Your Password</h1>
                <p class="text-muted">Enter your email and we&rsquo;ll send you a reset link.</p>
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

                    <form method="POST" action="{{ route('account.password.email') }}" novalidate>
                        @csrf

                        <div class="mb-4">
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

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Send Reset Link</button>
                        </div>
                    </form>

                </div>
            </div>

            <p class="text-center mt-3">
                <a href="{{ url()->previous() }}" class="text-muted small">&larr; Back</a>
            </p>

        </div>
    </div>
</div>
@endsection
