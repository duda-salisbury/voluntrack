<div class="col-6 col-xl-3">
    <div class="card">
        <div class="card-body">
            <div class="text-muted small mb-1">Total Entries</div>
            <div class="fs-2 fw-bold">{{ number_format($stats['total_entries']) }}</div>
        </div>
    </div>
</div>
<div class="col-6 col-xl-3">
    <div class="card">
        <div class="card-body">
            <div class="text-muted small mb-1">Total Hours</div>
            <div class="fs-2 fw-bold text-success">{{ number_format($stats['total_hours'], 1) }}</div>
        </div>
    </div>
</div>
<div class="col-6 col-xl-3">
    <div class="card">
        <div class="card-body">
            <div class="text-muted small mb-1">Matching Hours</div>
            <div class="fs-2 fw-bold text-primary">{{ number_format($stats['matched_hours'], 1) }}</div>
        </div>
    </div>
</div>
<div class="col-6 col-xl-3">
    <div class="card">
        <div class="card-body">
            <div class="text-muted small mb-1">Other Hours</div>
            <div class="fs-2 fw-bold">{{ number_format($stats['unmatched_hours'], 1) }}</div>
        </div>
    </div>
</div>
