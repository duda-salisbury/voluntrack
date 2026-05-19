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
    <td>
        {{ $entry->location->name }}
    </td>
    <td>
        <strong>{{ number_format($entry->hours, 2) }}</strong>
    </td>
    <td>
        <button 
            type="button"
            class="badge border-0 {{ $entry->is_matched ? 'bg-success' : 'bg-warning text-dark' }}"
            style="cursor: pointer;"
            hx-post="{{ route('admin.entries.toggle-match', $entry) }}"
            hx-target="#entry-{{ $entry->id }}"
            hx-swap="outerHTML"
        >
            {{ $entry->is_matched ? 'Matched' : 'Unmatched' }}
        </button>
    </td>
    <td>
        <textarea 
            class="form-control form-control-sm"
            placeholder="Add notes..."
            hx-post="{{ route('admin.entries.update-notes', $entry) }}"
            hx-trigger="change"
            hx-vals='{"notes": "this.value"}'
            name="notes"
        >{{ $entry->notes }}</textarea>
    </td>
    <td>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.entries.edit', $entry) }}" class="btn btn-sm btn-secondary">
                Edit
            </a>
            <form method="POST" action="{{ route('admin.entries.destroy', $entry) }}" class="m-0" onsubmit="return confirm('Delete this entry?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        </div>
    </td>
</tr>
