@extends('layouts.app')
@section('title', 'Hours Recorded')
@section('content')
<div class="container" style="max-width:640px">
    <div id="form-container" role="status" tabindex="-1">
        @include('hours.partials.success-inline')
        @guest
            <p class="text-center text-muted small mt-4">Want a history of future contributions?<br><a href="{{ route('org.account.register', $org) }}">Create an optional account</a>. Your hours are already saved.</p>
        @endguest
    </div>
</div>
@endsection
