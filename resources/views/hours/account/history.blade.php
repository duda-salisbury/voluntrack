@extends('layouts.app')

@section('title', 'My Volunteer History')

@section('content')
<div class="container py-4">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div>
            <h1 class="h3 mb-0">My Volunteer History</h1>
            <p class="text-muted mb-0">{{ $org->name }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('org.hours.index', $org) }}" class="btn btn-primary">Log Hours</a>
            <form method="POST" action="{{ route('org.account.logout', $org) }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Sign Out</button>
            </form>
        </div>
    </div>

    {{-- Summary card --}}
    <div class="row g-3 mb-4">
        <div class="col-sm-4">
            <div class="card text-center py-3">
                <div class="h2 mb-0 text-primary">{{ number_format($totalHours, 1) }}</div>
                <div class="text-muted small">Total Hours</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-3">
                <div class="h2 mb-0 text-primary">{{ $entries->total() }}</div>
                <div class="text-muted small">Total Entries</div>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="card text-center py-3">
                <div class="h2 mb-0 text-primary">{{ $org->name }}</div>
                <div class="text-muted small">Organization</div>
            </div>
        </div>
    </div>

    @if($entries->isEmpty())
        <div class="card">
            <div class="card-body text-center py-5 text-muted">
                <p class="mb-3">You haven&rsquo;t logged any hours yet.</p>
                <a href="{{ route('org.hours.index', $org) }}" class="btn btn-primary">Log Your First Entry</a>
            </div>
        </div>
    @else
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Location / event</th>
                            <th class="text-end">Hours</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($entries as $entry)
                        <tr>
                            <td class="text-nowrap">{{ $entry->volunteered_at->format('M j, Y') }}</td>
                            <td>{{ $entry->location?->name ?? '—' }}<span class="d-block small text-muted">{{ $entry->specialEvent?->name ?? 'Ordinary volunteering' }}</span></td>
                            <td class="text-end fw-semibold">{{ number_format($entry->hours, 2) }}</td>
                            <td class="text-muted small">{{ $entry->notes ?? '' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 d-flex justify-content-center">
            {{ $entries->links() }}
        </div>
    @endif

</div>
@endsection
