@extends('layouts.app')

@section('title', 'Success')

@section('content')
<div class="container-app py-5">
    <div class="text-center">
        <!-- Success Icon -->
        <div class="success-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none">
                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                <path d="M8 12L11 15L16 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        
        <!-- Success Message -->
        <h1 class="success-title">Thank You!</h1>
        <p class="success-message">
            Your volunteer hours have been successfully recorded.
        </p>
        
        <!-- Actions -->
        <div class="d-grid gap-3" style="max-width: 400px; margin: 0 auto;">
            <a href="{{ route('hours.index') }}" class="btn btn-primary btn-lg">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Log More Hours
            </a>
        </div>

        <!-- Additional Message -->
        <div class="mt-5">
            <div class="card" style="background-color: rgba(22, 163, 74, 0.05); border-color: var(--color-primary); max-width: 500px; margin: 0 auto;">
                <div class="card-body">
                    <p style="font-size: var(--text-sm); color: var(--color-text-secondary); margin-bottom: 0;">
                        <strong style="color: var(--color-primary-light);">Your contribution matters.</strong><br>
                        Every hour you track helps demonstrate the impact of volunteer trail work 
                        and supports future funding opportunities.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
