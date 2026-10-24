@extends('layouts.admin')

@section('title', 'Add Entry')

@section('content')
<div class="container py-4">
    <div class="mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm mb-3">← Back to Dashboard</a>
        <h1 class="h3 mb-1">Add Volunteer Entry</h1>
        <p class="text-muted">Create a new volunteer hour entry</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.entries.store') }}">
                @csrf

                <div class="row">
                    @if(auth()->user()->isSuperAdmin())
                    <!-- Organization (superadmin only) -->
                    <div class="col-12 mb-4">
                        <label for="org-search" class="form-label fw-semibold">
                            Organization <span class="text-danger">*</span>
                        </label>
                        <div class="org-combobox position-relative" style="max-width: 400px;">
                            <input
                                type="text"
                                id="org-search"
                                class="form-control @error('organization_id') is-invalid @enderror"
                                placeholder="Search organizations…"
                                autocomplete="off"
                                aria-expanded="false"
                                aria-autocomplete="list"
                                aria-controls="org-listbox"
                            >
                            <input type="hidden" name="organization_id" id="org-id-input" value="{{ old('organization_id') }}">
                            <ul
                                id="org-listbox"
                                role="listbox"
                                class="list-unstyled mb-0 border rounded bg-white shadow-sm position-absolute"
                                style="display:none; z-index:1000; max-height:260px; overflow-y:auto; top:100%; left:0; min-width:100%;"
                            ></ul>
                        </div>
                        @error('organization_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    <!-- Location -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="location_id" class="form-label fw-semibold">
                            Location <span class="text-danger">*</span>
                        </label>
                        <select 
                            name="location_id" 
                            id="location_id" 
                            class="form-select @error('location_id') is-invalid @enderror"
                            required
                        >
                            <option value="">@if(auth()->user()->isSuperAdmin())Select an organization first…@else Select a location…@endif</option>
                            @if(!auth()->user()->isSuperAdmin())
                                @foreach($locations as $location)
                                    <option value="{{ $location->id }}" {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            @else
                                {{-- Options injected by JS after org selection; data stored in hidden JSON below --}}
                            @endif
                        </select>
                        @error('location_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 mb-4">
                        <label for="special_event_id" class="form-label fw-semibold">Special event <span class="text-muted fw-normal">(optional)</span></label>
                        <select name="special_event_id" id="special_event_id" class="form-select @error('special_event_id') is-invalid @enderror">
                            <option value="">Everyday volunteering / no event</option>
                            @foreach($events as $event)
                                <option value="{{ $event->id }}" data-org="{{ $event->organization_id }}" @selected(old('special_event_id') == $event->id)>{{ $event->name }}@if(auth()->user()->isSuperAdmin()) ({{ $event->organization?->name }})@endif</option>
                            @endforeach
                        </select>
                        @error('special_event_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <!-- Date -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="volunteered_at" class="form-label fw-semibold">
                            Date <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="date" 
                            name="volunteered_at" 
                            id="volunteered_at" 
                            class="form-control @error('volunteered_at') is-invalid @enderror"
                            value="{{ old('volunteered_at', date('Y-m-d')) }}"
                            required
                        >
                        @error('volunteered_at')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Hours -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="hours" class="form-label fw-semibold">
                            Hours <span class="text-danger">*</span>
                        </label>
                        <input 
                            type="number" 
                            name="hours" 
                            id="hours" 
                            class="form-control @error('hours') is-invalid @enderror"
                            value="{{ old('hours') }}"
                            min="0.01"
                            max="24"
                            step="any"
                            placeholder="4.5"
                            required
                        >
                        @error('hours')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Volunteer -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="volunteer-search" class="form-label fw-semibold">
                            Volunteer Name <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <div class="volunteer-combobox position-relative">
                            <input
                                type="text"
                                id="volunteer-search"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Search registered volunteers or type a name…"
                                autocomplete="off"
                                value="{{ old('name') }}"
                            >
                            <input type="hidden" name="volunteer_user_id" id="volunteer-user-id" value="{{ old('volunteer_user_id') }}">
                            <input type="hidden" name="name" id="volunteer-name-input" value="{{ old('name') }}">
                            <ul
                                id="volunteer-listbox"
                                role="listbox"
                                class="list-unstyled mb-0 border rounded bg-white shadow-sm position-absolute w-100"
                                style="display:none; z-index:1000; max-height:240px; overflow-y:auto; top:100%; left:0;"
                            ></ul>
                        </div>
                        <div class="form-text">Leave blank to log anonymously. Start typing to find a registered volunteer.</div>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Matched Checkbox -->
                    <div class="col-12 mb-4">
                        <div class="form-check">
                            <input 
                                class="form-check-input" 
                                type="checkbox" 
                                name="is_matched" 
                                id="is_matched"
                                value="1"
                                {{ old('is_matched') ? 'checked' : '' }}
                            >
                            <label class="form-check-label fw-semibold" for="is_matched">Mark as Matched Hours</label>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="col-12 mb-4">
                        <label for="notes" class="form-label fw-semibold">
                            Notes <span class="text-muted fw-normal">(optional)</span>
                        </label>
                        <textarea 
                            name="notes" 
                            id="notes" 
                            class="form-control @error('notes') is-invalid @enderror"
                            rows="4"
                            placeholder="Add any additional notes..."
                        >{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Submit -->
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Create Entry</button>
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@if(auth()->user()->isSuperAdmin())
<script type="application/json" id="org-data">@json($organizations->values())</script>
<script type="application/json" id="location-data">@json($locationsJson)</script>
<script type="application/json" id="volunteer-data">@json($volunteersJson)</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const orgs        = JSON.parse(document.getElementById('org-data').textContent);
    const allOptions  = JSON.parse(document.getElementById('location-data').textContent);
    const searchInput = document.getElementById('org-search');
    const hiddenInput = document.getElementById('org-id-input');
    const listbox     = document.getElementById('org-listbox');
    const locationSel = document.getElementById('location_id');

    let activeIndex = -1;

    function filterLocations(orgId) {
        const eventSelect = document.getElementById('special_event_id');
        eventSelect.querySelectorAll('option[data-org]').forEach(option => {
            const available = String(option.dataset.org) === String(orgId);
            option.hidden = !available;
            option.disabled = !available;
            if (!available && option.selected) eventSelect.value = '';
        });
        const current = locationSel.value;
        locationSel.innerHTML = '';
        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = 'Select a location…';
        locationSel.appendChild(placeholder);
        allOptions
            .filter(o => String(o.org) === String(orgId))
            .forEach(o => {
                const opt = document.createElement('option');
                opt.value = o.id;
                opt.textContent = o.name;
                if (String(o.id) === String(current)) opt.selected = true;
                locationSel.appendChild(opt);
            });
    }

    function escapeHtml(str) {
        return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function openList()  { listbox.style.display = 'block'; }
    function closeList() { listbox.style.display = 'none'; activeIndex = -1; }

    function renderList(query) {
        const q = query.trim().toLowerCase();
        const items = q === '' ? orgs : orgs.filter(o => o.name.toLowerCase().includes(q) || o.slug.toLowerCase().includes(q));
        listbox.innerHTML = '';
        activeIndex = -1;
        if (!items.length) {
            listbox.innerHTML = '<li class="px-3 py-2 text-muted small">No organizations found</li>';
            openList(); return;
        }
        const groups = {};
        items.forEach(o => { const l = o.name[0].toUpperCase(); (groups[l] = groups[l] || []).push(o); });
        Object.keys(groups).sort().forEach(letter => {
            const hdr = document.createElement('li');
            hdr.className = 'px-3 pt-2 pb-1 text-muted fw-semibold border-bottom';
            hdr.style.cssText = 'font-size:.7rem;letter-spacing:.05em;';
            hdr.textContent = letter;
            hdr.setAttribute('aria-hidden', 'true');
            listbox.appendChild(hdr);
            groups[letter].forEach(org => {
                const li = document.createElement('li');
                li.className = 'org-item px-3 py-2';
                li.style.cursor = 'pointer';
                li.dataset.id = org.id; li.dataset.name = org.name;
                li.setAttribute('role', 'option');
                li.innerHTML = `<span>${escapeHtml(org.name)}</span> <small class="text-muted ms-1">${escapeHtml(org.slug)}</small>`;
                li.addEventListener('mousedown', e => { e.preventDefault(); selectOrg(org); });
                li.addEventListener('mouseover', () => setActive(li));
                listbox.appendChild(li);
            });
        });
        openList();
    }

    function setActive(el) {
        listbox.querySelectorAll('.org-item').forEach(i => i.classList.remove('bg-primary','text-white'));
        if (el) el.classList.add('bg-primary','text-white');
    }

    function selectOrg(org) {
        searchInput.value = org.name;
        hiddenInput.value = org.id;
        closeList();
        filterLocations(org.id);
        initVolunteerCombobox(String(org.id));
    }

    searchInput.addEventListener('focus', () => renderList(searchInput.value));
    searchInput.addEventListener('input', () => { hiddenInput.value = ''; renderList(searchInput.value); });
    searchInput.addEventListener('blur', () => {
        setTimeout(() => {
            if (!hiddenInput.value && searchInput.value.trim()) searchInput.value = '';
            closeList();
        }, 150);
    });
    searchInput.addEventListener('keydown', e => {
        const items = Array.from(listbox.querySelectorAll('.org-item'));
        if (!items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); activeIndex = Math.min(activeIndex+1, items.length-1); setActive(items[activeIndex]); items[activeIndex].scrollIntoView({block:'nearest'}); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); activeIndex = Math.max(activeIndex-1, 0); setActive(items[activeIndex]); items[activeIndex].scrollIntoView({block:'nearest'}); }
        else if (e.key === 'Enter') { e.preventDefault(); if (activeIndex >= 0) selectOrg({id: items[activeIndex].dataset.id, name: items[activeIndex].dataset.name}); }
        else if (e.key === 'Escape') closeList();
    });
    document.addEventListener('click', e => { if (!searchInput.closest('.org-combobox').contains(e.target)) closeList(); });

    // Volunteer combobox (superadmin: filtered by org; org-admin: all their volunteers)
    const allVolunteers = JSON.parse(document.getElementById('volunteer-data').textContent);
    function initVolunteerCombobox(orgId) {
        const volunteers = orgId ? allVolunteers.filter(v => String(v.org) === orgId) : allVolunteers;
        setupVolunteerCombobox(volunteers);
    }

    // Restore on validation failure
    const currentOrgId = hiddenInput.value;
    if (currentOrgId) {
        const match = orgs.find(o => String(o.id) === String(currentOrgId));
        if (match) { searchInput.value = match.name; filterLocations(currentOrgId); initVolunteerCombobox(currentOrgId); }
    } else {
        filterLocations(null);
        initVolunteerCombobox(null);
    }
});
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    // For org-admins, volunteers are already scoped — just init with full list from JSON or DOM
    @if(!auth()->user()->isSuperAdmin())
    const allVolunteers = @json($volunteersJson);
    setupVolunteerCombobox(allVolunteers);
    @endif
});

function setupVolunteerCombobox(volunteers) {
    const vSearch  = document.getElementById('volunteer-search');
    const vUserId  = document.getElementById('volunteer-user-id');
    const vName    = document.getElementById('volunteer-name-input');
    const vListbox = document.getElementById('volunteer-listbox');
    if (!vSearch) return;

    // Clear previous listeners by replacing the element clone
    const newSearch = vSearch.cloneNode(true);
    vSearch.parentNode.replaceChild(newSearch, vSearch);
    const input = document.getElementById('volunteer-search');

    let vActive = -1;

    function escHtml(str) {
        return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
    function openV()  { vListbox.style.display = 'block'; }
    function closeV() { vListbox.style.display = 'none'; vActive = -1; }

    function renderVolunteers(query) {
        const q = query.trim().toLowerCase();
        vListbox.innerHTML = '';
        vActive = -1;

        // Always show "Anonymous" option first
        const anonLi = document.createElement('li');
        anonLi.className = 'vol-item px-3 py-2 text-muted fst-italic';
        anonLi.style.cursor = 'pointer';
        anonLi.dataset.id = '';
        anonLi.dataset.name = '';
        anonLi.setAttribute('role', 'option');
        anonLi.textContent = 'Anonymous (no name)';
        anonLi.addEventListener('mousedown', e => { e.preventDefault(); selectVolunteer({id: '', name: ''}); });
        anonLi.addEventListener('mouseover', () => setVActive(anonLi));
        vListbox.appendChild(anonLi);

        const matched = q === '' ? volunteers : volunteers.filter(v =>
            v.name.toLowerCase().includes(q) || v.email.toLowerCase().includes(q)
        );

        if (matched.length === 0 && q !== '') {
            // Allow free-text entry
            const freeLi = document.createElement('li');
            freeLi.className = 'vol-item px-3 py-2';
            freeLi.style.cursor = 'pointer';
            freeLi.dataset.id = '';
            freeLi.dataset.name = query.trim();
            freeLi.setAttribute('role', 'option');
            freeLi.innerHTML = `Use name: <strong>${escHtml(query.trim())}</strong>`;
            freeLi.addEventListener('mousedown', e => { e.preventDefault(); selectVolunteer({id: '', name: query.trim()}); });
            freeLi.addEventListener('mouseover', () => setVActive(freeLi));
            vListbox.appendChild(freeLi);
        }

        matched.forEach(v => {
            const li = document.createElement('li');
            li.className = 'vol-item px-3 py-2';
            li.style.cursor = 'pointer';
            li.dataset.id = v.id;
            li.dataset.name = v.name;
            li.setAttribute('role', 'option');
            li.innerHTML = `<span>${escHtml(v.name)}</span> <small class="text-muted ms-1">${escHtml(v.email)}</small>`;
            li.addEventListener('mousedown', e => { e.preventDefault(); selectVolunteer(v); });
            li.addEventListener('mouseover', () => setVActive(li));
            vListbox.appendChild(li);
        });

        openV();
    }

    function setVActive(el) {
        vListbox.querySelectorAll('.vol-item').forEach(i => i.classList.remove('bg-primary','text-white'));
        if (el) el.classList.add('bg-primary','text-white');
    }

    function selectVolunteer(v) {
        input.value    = v.name;
        vUserId.value  = v.id;
        vName.value    = v.name;
        closeV();
    }

    input.addEventListener('focus', () => renderVolunteers(input.value));
    input.addEventListener('input', () => { vUserId.value = ''; vName.value = input.value; renderVolunteers(input.value); });
    input.addEventListener('blur', () => { setTimeout(closeV, 150); });
    input.addEventListener('keydown', e => {
        const items = Array.from(vListbox.querySelectorAll('.vol-item'));
        if (!items.length) return;
        if (e.key === 'ArrowDown') { e.preventDefault(); vActive = Math.min(vActive+1, items.length-1); setVActive(items[vActive]); items[vActive].scrollIntoView({block:'nearest'}); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); vActive = Math.max(vActive-1, 0); setVActive(items[vActive]); items[vActive].scrollIntoView({block:'nearest'}); }
        else if (e.key === 'Enter') { e.preventDefault(); if (vActive >= 0) selectVolunteer({id: items[vActive].dataset.id, name: items[vActive].dataset.name}); }
        else if (e.key === 'Escape') closeV();
    });
    document.addEventListener('click', e => {
        const wrap = document.querySelector('.volunteer-combobox');
        if (wrap && !wrap.contains(e.target)) closeV();
    });
}
</script>
