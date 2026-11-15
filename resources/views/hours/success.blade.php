@extends('layouts.app')
@section('title', 'Hours Recorded')
@section('body-class', 'volunteer-log-page')
@section('content')
<div class="container volunteer-form">
    <div id="form-container" role="status" tabindex="-1">
        @include('hours.partials.success-inline')
        @guest
            <p class="text-center text-muted small mt-3"><a href="{{ route('org.account.register', $org) }}">Create an optional account</a> to keep a history of future hours.</p>
        @endguest
    </div>
</div>
@endsection
