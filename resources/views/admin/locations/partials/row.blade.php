{{--
    HTMX row partial — re-rendered in-place after toggleActive.
    Must produce a single <tr> with id="location-{{ $location->id }}".
--}}
<tr id="location-{{ $location->id }}">
    <td>
        <div class="fw-semibold">{{ $location->name }}</div>
        @if($location->description)
            <div class="text-muted small text-truncate" style="max-width: 280px;">{{ $location->description }}</div>
        @endif
    </td>
    @if(auth()->user()->isSuperAdmin())
    <td class="text-muted small">{{ $location->organization->name ?? '—' }}</td>
    @endif
    <td>
        @if($location->source && $location->source !== 'manual')
            <span class="badge bg-info-subtle text-info border border-info-subtle">{{ $location->source }}</span>
        @else
            <span class="text-muted small">manual</span>
        @endif
    </td>
    <td class="text-center">
        {{ number_format($location->volunteer_entries_count) }}
    </td>
    <td class="text-center">
        <button
            type="button"
            class="badge border-0 {{ $location->is_active ? 'bg-success' : 'bg-secondary' }}"
            style="cursor: pointer; font-size: .75rem; padding: .35em .65em;"
            hx-patch="{{ route('admin.locations.toggle-active', $location) }}"
            hx-target="#location-{{ $location->id }}"
            hx-swap="outerHTML"
            title="Click to {{ $location->is_active ? 'deactivate' : 'activate' }}"
        >
            {{ $location->is_active ? 'Active' : 'Inactive' }}
        </button>
    </td>
    <td>
        <div class="d-flex gap-2 justify-content-end">
            <a href="{{ route('admin.locations.edit', $location) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
            @if($location->volunteer_entries_count === 0)
            <form method="POST" action="{{ route('admin.locations.destroy', $location) }}" class="m-0"
                  onsubmit="return confirm('Delete this location? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
            @endif
        </div>
    </td>
</tr>
