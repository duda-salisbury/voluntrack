@extends('layouts.admin')

@section('title', 'Organizations')

@section('content')
<div class="container-fluid" style="max-width: 1000px;">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">Organizations</h1>
            <p class="text-muted mb-0">All organizations using this Voluntrack instance.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.organizations.create') }}" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" class="me-1" style="vertical-align:-2px;" aria-hidden="true">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                New Organization
            </a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">← Dashboard</a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th class="text-center">Locations</th>
                        <th class="text-center">Entries</th>
                        <th class="text-center">Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($organizations as $organization)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                {{-- Colour swatch --}}
                                <span class="rounded-circle d-inline-block flex-shrink-0"
                                      style="width:14px;height:14px;background:{{ $organization->accent_color }};border:1px solid rgba(0,0,0,.1);"
                                      title="{{ $organization->accent_color }}"></span>
                                @if($organization->logo_url)
                                    <img src="{{ $organization->logo_url }}" alt="" style="height:20px;width:auto;object-fit:contain;">
                                @endif
                                <strong>{{ $organization->name }}</strong>
                            </div>
                        </td>
                        <td><code class="small">{{ $organization->slug }}</code></td>
                        <td class="text-center">{{ number_format($organization->locations_count) }}</td>
                        <td class="text-center">{{ number_format($organization->volunteer_entries_count) }}</td>
                        <td class="text-center">
                            @if($organization->is_active)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                @if($organization->is_active)
                                    <a href="{{ route('org.hours.index', $organization) }}" target="_blank"
                                       class="btn btn-sm btn-outline-secondary" title="Preview public form">↗</a>
                                @endif
                                <a href="{{ route('admin.organizations.edit', $organization) }}"
                                   class="btn btn-sm btn-outline-primary">Edit</a>
                                <form method="POST" action="{{ route('admin.organizations.toggle-active', $organization) }}" class="m-0">
                                    @csrf @method('PATCH')
                                    <button type="submit"
                                        class="btn btn-sm {{ $organization->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}"
                                        onclick="return confirm('{{ $organization->is_active ? 'Deactivate' : 'Activate' }} this organization?')"
                                    >{{ $organization->is_active ? 'Deactivate' : 'Activate' }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.organizations.destroy', $organization) }}" class="m-0">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Permanently delete \"{{ addslashes($organization->name) }}\"? This cannot be undone.')"
                                    >Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            No organizations yet. <a href="{{ route('admin.organizations.create') }}">Create one</a>.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
