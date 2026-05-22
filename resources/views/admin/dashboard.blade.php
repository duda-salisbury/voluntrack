@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid" style="max-width: 1400px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Admin Dashboard</h1>
            <p class="text-muted mb-0">
                Manage volunteer hour entries
                @if(isset($org) && $org)
                    &nbsp;·&nbsp;<span class="fw-semibold">{{ $org->name }}</span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.entries.create') }}" class="btn btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 5V19M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Add Entry
            </a>
            {{-- Org-admin: quick access to their branding --}}
            @if(isset($org) && $org)
                <a href="{{ route('admin.branding.edit') }}" class="btn btn-outline-secondary">Branding</a>
                <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary">Locations</a>
            @endif
            {{-- Super-admin: manage all orgs --}}
            @if(auth()->user()->isSuperAdmin())
                <a href="{{ route('admin.organizations.index') }}" class="btn btn-outline-secondary">Organizations</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>
        </div>
    </div>

    <!-- Stats Cards -->
    <div
        class="row g-3 mb-4"
        id="stats-cards"
        hx-get="{{ route('admin.stats') }}"
        hx-trigger="statsUpdated from:body"
        hx-swap="innerHTML"
    >
        @include('admin.partials.stats-cards', ['stats' => $stats])
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.dashboard') }}" class="row g-3">
                @if(auth()->user()->isSuperAdmin())
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="org-search" class="form-label">Organization</label>
                    <div class="org-combobox position-relative">
                        <input
                            type="text"
                            id="org-search"
                            class="form-control"
                            placeholder="Search organizations…"
                            autocomplete="off"
                            aria-expanded="false"
                            aria-autocomplete="list"
                            aria-controls="org-listbox"
                        >
                        <input type="hidden" name="organization_id" id="org-id-input" value="{{ request('organization_id') }}">
                        <ul
                            id="org-listbox"
                            role="listbox"
                            class="org-combobox__list list-unstyled mb-0 border rounded bg-white shadow-sm position-absolute w-100"
                            style="display:none; z-index:1000; max-height:260px; overflow-y:auto; top:100%; left:0;"
                        ></ul>
                    </div>
                    <script type="application/json" id="org-data">@json($organizations->values())</script>
                </div>
                @endif
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="location_id" class="form-label">Location</label>
                    <select name="location_id" id="location_id" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ request('location_id') == $location->id ? 'selected' : '' }}>
                                {{ $location->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="date_from" class="form-label">Date From</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="date_to" class="form-label">Date To</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-md-6 col-lg-3">
                    <label for="matched" class="form-label">Matched Status</label>
                    <select name="matched" id="matched" class="form-select">
                        <option value="">All</option>
                        <option value="yes" {{ request('matched') === 'yes' ? 'selected' : '' }}>Matched</option>
                        <option value="no" {{ request('matched') === 'no' ? 'selected' : '' }}>Unmatched</option>
                    </select>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>
    </div>

    @if(auth()->user()->isSuperAdmin())
    <script>
    (function () {
        // Organisation data embedded in a JSON script tag — avoids Blade/JS escaping issues
        const orgs = JSON.parse(document.getElementById('org-data').textContent);

        const searchInput  = document.getElementById('org-search');
        const hiddenInput  = document.getElementById('org-id-input');
        const listbox      = document.getElementById('org-listbox');

        let activeIndex = -1;

        // Restore label when page loads with an existing filter value
        const currentId = hiddenInput.value;
        if (currentId) {
            const match = orgs.find(o => String(o.id) === String(currentId));
            if (match) searchInput.value = match.name;
        }

        function buildItems(query) {
            const q = query.trim().toLowerCase();
            return q === ''
                ? orgs
                : orgs.filter(o => o.name.toLowerCase().includes(q) || o.slug.toLowerCase().includes(q));
        }

        function renderList(items) {
            listbox.innerHTML = '';
            activeIndex = -1;

            if (items.length === 0) {
                listbox.innerHTML = '<li class="px-3 py-2 text-muted small">No organizations found</li>';
                openList();
                return;
            }

            // Group alphabetically by first letter
            const groups = {};
            items.forEach(o => {
                const letter = o.name[0].toUpperCase();
                (groups[letter] = groups[letter] || []).push(o);
            });

            Object.keys(groups).sort().forEach(letter => {
                const header = document.createElement('li');
                header.className = 'px-3 pt-2 pb-1 text-muted small fw-semibold border-bottom';
                header.style.fontSize = '0.7rem';
                header.style.letterSpacing = '0.05em';
                header.textContent = letter;
                header.setAttribute('aria-hidden', 'true');
                listbox.appendChild(header);

                groups[letter].forEach(org => {
                    const li = document.createElement('li');
                    li.className = 'org-combobox__item px-3 py-2';
                    li.style.cursor = 'pointer';
                    li.dataset.id   = org.id;
                    li.dataset.name = org.name;
                    li.setAttribute('role', 'option');
                    li.innerHTML = `<span>${escapeHtml(org.name)}</span> <small class="text-muted ms-1">${escapeHtml(org.slug)}</small>`;

                    li.addEventListener('mousedown', e => {
                        e.preventDefault(); // prevent blur before click
                        selectOrg(org);
                    });
                    li.addEventListener('mouseover', () => setActive(li));
                    listbox.appendChild(li);
                });
            });

            openList();
        }

        function escapeHtml(str) {
            return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        }

        function openList()  { listbox.style.display = 'block'; searchInput.setAttribute('aria-expanded', 'true'); }
        function closeList() { listbox.style.display = 'none';  searchInput.setAttribute('aria-expanded', 'false'); activeIndex = -1; }

        function setActive(el) {
            listbox.querySelectorAll('.org-combobox__item').forEach(i => i.classList.remove('active', 'bg-primary', 'text-white'));
            if (el) el.classList.add('active', 'bg-primary', 'text-white');
        }

        function selectOrg(org) {
            searchInput.value  = org.name;
            hiddenInput.value  = org.id;
            closeList();
        }

        searchInput.addEventListener('focus', () => renderList(searchInput.value));

        searchInput.addEventListener('input', () => {
            hiddenInput.value = ''; // clear selection while typing
            renderList(searchInput.value);
        });

        searchInput.addEventListener('blur', () => {
            // If nothing selected and field has text, clear it to avoid partial filter
            setTimeout(() => {
                if (!hiddenInput.value && searchInput.value.trim() !== '') {
                    searchInput.value = '';
                }
                closeList();
            }, 150);
        });

        searchInput.addEventListener('keydown', e => {
            const items = Array.from(listbox.querySelectorAll('.org-combobox__item'));
            if (!items.length) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                setActive(items[activeIndex]);
                items[activeIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                setActive(items[activeIndex]);
                items[activeIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0 && items[activeIndex]) {
                    selectOrg({ id: items[activeIndex].dataset.id, name: items[activeIndex].dataset.name });
                    searchInput.closest('form').submit();
                }
            } else if (e.key === 'Escape') {
                closeList();
            }
        });

        // Close when clicking outside
        document.addEventListener('click', e => {
            if (!searchInput.closest('.org-combobox').contains(e.target)) closeList();
        });
    })();
    </script>
    @endif

    <!-- Entries Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Name</th>
                        @if(auth()->user()->isSuperAdmin())
                            <th>Organization</th>
                        @endif
                        <th>Location</th>
                        <th>Hours</th>
                        <th>Matched</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        @include('admin.partials.entry-row', ['entry' => $entry])
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isSuperAdmin() ? 8 : 7 }}" class="text-center py-5 text-muted">No entries found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($entries->hasPages())
            <div class="card-footer">
                {{ $entries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection


