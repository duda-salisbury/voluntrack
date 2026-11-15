@extends('layouts.app')

@section('title', 'Log Volunteer Hours')
@section('body-class', 'volunteer-log-page')

@section('content')
<div class="container volunteer-form">
    <div id="form-container">
        <header class="volunteer-form-heading">
            <h1>{{ $org->config->welcomeTitle }}</h1>
            <p class="text-muted mb-0">{{ $org->config->welcomeMessage }}</p>
        </header>
        @include('hours.partials.form')
    </div>
</div>
@endsection
