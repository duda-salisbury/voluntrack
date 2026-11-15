<tr id="entry-{{ $entry->id }}">
    <td style="white-space: nowrap;">
        {{ $entry->volunteered_at->format('M j, Y') }}
    </td>
    <td>
        @if($entry->is_group_total)
            <span class="badge bg-success-subtle text-success border border-success-subtle">Group total</span>
            @if($entry->participant_count !== null)<span class="d-block small text-muted">{{ number_format($entry->participant_count) }} reported {{ Str::plural('participant', $entry->participant_count) }}</span>@endif
            <span class="d-block small text-muted">Entered by {{ $entry->createdByAdmin?->name ?? 'Unavailable account' }}</span>
        @else
            {{ $entry->name ?: 'Anonymous' }}
        @endif
        @if(! $entry->is_group_total && $entry->volunteer_user_id)
            <span class="badge bg-primary ms-1" title="Registered volunteer account">Account</span>
        @endif
    </td>
    @if(auth()->user()->isPlatformWorkspace())
    <td>
        <span class="badge bg-secondary">{{ $entry->organization->name ?? '—' }}</span>
    </td>
    @endif
    <td>
        {{ $entry->location?->name ?? 'Archived location' }}
        <span class="d-block small text-muted mt-1">{{ $entry->specialEvent?->name ?? 'Ordinary volunteering' }}</span>
    </td>

    {{-- Hours inline edit --}}
    <td>
        <input
            type="number"
            class="form-control form-control-sm"
            style="width: 80px;"
            value="{{ $entry->hours }}"
            min="0.01"
            max="{{ $entry->is_group_total ? \App\Models\VolunteerEntry::MAX_GROUP_HOURS : 24 }}"
            step="any"
            name="value"
            id="entry-hours-{{ $entry->id }}"
            aria-label="Hours for entry {{ $entry->id }}"
            hx-patch="{{ route('admin.entries.update-inline', $entry) }}"
            hx-trigger="change"
            hx-target="#entry-{{ $entry->id }}"
            hx-swap="outerHTML"
            hx-vals='{"field":"hours"}'
            hx-headers='{"Accept":"application/json"}'
        >
    </td>

    {{-- Matched inline toggle --}}
    <td>
        <button
            type="button"
            class="btn btn-sm {{ $entry->is_matched ? 'btn-outline-success' : 'btn-outline-secondary' }}"
            style="cursor: pointer; font-size: .8rem; padding: .35em .6em;"
            title="Click to toggle"
            aria-label="Toggle matching classification for entry {{ $entry->id }}"
            aria-pressed="{{ $entry->is_matched ? 'true' : 'false' }}"
            hx-patch="{{ route('admin.entries.update-inline', $entry) }}"
            hx-target="#entry-{{ $entry->id }}"
            hx-swap="outerHTML"
            hx-vals='{"field":"is_matched","value":"{{ $entry->is_matched ? 'false' : 'true' }}"}'
            hx-headers='{"Accept":"application/json"}'
        >
            {{ $entry->is_matched ? 'Matching' : 'Not matching' }}
        </button>
    </td>

    {{-- Notes inline edit --}}
    <td style="min-width: 180px;">
        <textarea
            class="form-control form-control-sm"
            rows="2"
            placeholder="Add notes…"
            aria-label="Notes for entry {{ $entry->id }}"
            style="resize: vertical; min-height: 2.5rem;"
            name="value"
            id="entry-notes-{{ $entry->id }}"
            hx-patch="{{ route('admin.entries.update-inline', $entry) }}"
            hx-trigger="change"
            hx-target="#entry-{{ $entry->id }}"
            hx-swap="outerHTML"
            hx-vals='{"field":"notes"}'
            hx-headers='{"Accept":"application/json"}'
        >{{ $entry->notes }}</textarea>
    </td>

    <td>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.entries.edit', $entry) }}" class="btn btn-sm btn-secondary">Edit</a>
            <form method="POST" action="{{ route('admin.entries.destroy', $entry) }}" class="m-0" onsubmit="return confirm('Delete this entry?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        </div>
    </td>
</tr>
