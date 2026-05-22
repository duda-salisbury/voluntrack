<tr id="entry-{{ $entry->id }}">
    <td style="white-space: nowrap;">
        {{ $entry->volunteered_at->format('M j, Y') }}
    </td>
    <td>
        {{ $entry->name ?: 'Anonymous' }}
        @if($entry->volunteer_user_id)
            <span class="badge bg-primary ms-1" title="Registered volunteer account">Account</span>
        @endif
    </td>
    @if(auth()->user()->isSuperAdmin())
    <td>
        <span class="badge bg-secondary">{{ $entry->organization->name ?? '—' }}</span>
    </td>
    @endif
    <td>
        {{ $entry->location->name }}
    </td>

    {{-- Hours inline edit --}}
    <td>
        <input
            type="number"
            class="form-control form-control-sm"
            style="width: 80px;"
            value="{{ $entry->hours }}"
            min="0.01"
            max="24"
            step="any"
            hx-patch="{{ route('admin.entries.update-inline', $entry) }}"
            hx-trigger="change"
            hx-target="#entry-{{ $entry->id }}"
            hx-swap="outerHTML"
            onchange="this.closest('tr').querySelectorAll('[data-inline-hours]').forEach(()=>{}); htmx.ajax('PATCH','{{ route('admin.entries.update-inline', $entry) }}',{target:'#entry-{{ $entry->id }}',swap:'outerHTML',values:{field:'hours',value:this.value}})"
        >
    </td>

    {{-- Matched inline toggle --}}
    <td>
        <button
            type="button"
            class="badge border-0 {{ $entry->is_matched ? 'bg-success' : 'bg-warning text-dark' }}"
            style="cursor: pointer; font-size: .8rem; padding: .35em .6em;"
            title="Click to toggle"
            hx-patch="{{ route('admin.entries.update-inline', $entry) }}"
            hx-target="#entry-{{ $entry->id }}"
            hx-swap="outerHTML"
            hx-vals='{"field":"is_matched","value":"{{ $entry->is_matched ? 'false' : 'true' }}"}'
        >
            {{ $entry->is_matched ? '✓ Matched' : '✗ Unmatched' }}
        </button>
    </td>

    {{-- Notes inline edit --}}
    <td style="min-width: 180px;">
        <textarea
            class="form-control form-control-sm"
            rows="2"
            placeholder="Add notes…"
            style="resize: vertical; min-height: 2.5rem;"
            onchange="htmx.ajax('PATCH','{{ route('admin.entries.update-inline', $entry) }}',{target:'#entry-{{ $entry->id }}',swap:'outerHTML',values:{field:'notes',value:this.value}})"
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
