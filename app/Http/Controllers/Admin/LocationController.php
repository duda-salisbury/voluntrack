<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** The org the current admin is scoped to (null = super-admin). */
    private function adminOrgId(): ?int
    {
        return auth()->user()->organization_id;
    }

    /** Base query — super-admins see all; org-admins see their own. */
    private function baseQuery()
    {
        $q = Location::with('organization')->withCount('volunteerEntries');

        $orgId = $this->adminOrgId();
        if ($orgId !== null) {
            $q->where('organization_id', $orgId);
        }

        return $q;
    }

    /** Ensure the given location belongs to the admin's org (or allow super-admin). */
    private function authorizeLocation(Location $location): void
    {
        $orgId = $this->adminOrgId();
        if ($orgId !== null && $location->organization_id !== $orgId) {
            abort(403);
        }
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $orgId = $this->adminOrgId();

        $query = $this->baseQuery();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('org') && $orgId === null) {
            $query->where('organization_id', $request->org);
        }

        $locations = $query->orderBy('name')->paginate(25)->withQueryString();

        $organizations = $orgId === null
            ? Organization::orderBy('name')->get()
            : collect();

        return view('admin.locations.index', compact('locations', 'organizations'));
    }

    public function create(): View
    {
        $orgId = $this->adminOrgId();

        $organizations = $orgId === null
            ? Organization::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('admin.locations.create', compact('organizations', 'orgId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $this->adminOrgId();

        $validated = $request->validate([
            'organization_id' => $orgId === null ? 'required|exists:organizations,id' : 'nullable',
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string|max:1000',
            'source'          => 'nullable|string|max:50',
            'external_id'     => 'nullable|string|max:255',
            'is_active'       => 'boolean',
        ]);

        // Org-admins are always scoped to their own org
        $resolvedOrgId = $orgId ?? $validated['organization_id'];
        $validated['organization_id'] = $resolvedOrgId;
        $validated['source']          = $validated['source'] ?? 'manual';
        $validated['is_active']       = $request->boolean('is_active', true);

        // Auto-generate slug if not supplied externally
        $validated['slug'] = Location::generateSlug($validated['name'], $resolvedOrgId);

        Location::create($validated);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location "' . $validated['name'] . '" created.');
    }

    public function edit(Location $location): View
    {
        $this->authorizeLocation($location);

        $orgId = $this->adminOrgId();

        $organizations = $orgId === null
            ? Organization::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('admin.locations.edit', compact('location', 'organizations', 'orgId'));
    }

    public function update(Request $request, Location $location): RedirectResponse
    {
        $this->authorizeLocation($location);

        $orgId = $this->adminOrgId();

        $validated = $request->validate([
            'organization_id' => $orgId === null ? 'required|exists:organizations,id' : 'nullable',
            'name'            => 'required|string|max:255',
            'description'     => 'nullable|string|max:1000',
            'source'          => 'nullable|string|max:50',
            'external_id'     => 'nullable|string|max:255',
            'is_active'       => 'boolean',
        ]);

        $resolvedOrgId = $orgId ?? $validated['organization_id'];
        $validated['organization_id'] = $resolvedOrgId;
        $validated['source']          = $validated['source'] ?? $location->source;
        $validated['is_active']       = $request->boolean('is_active', false);

        if ((int) $resolvedOrgId !== $location->organization_id && $location->volunteerEntries()->exists()) {
            return back()->withErrors(['organization_id' => 'This location has recorded hours and cannot be moved to another organization. Create a new location there instead.'])->withInput();
        }

        // Regenerate slug only if the name changed
        if ($validated['name'] !== $location->name) {
            $validated['slug'] = Location::generateSlug($validated['name'], $resolvedOrgId, $location->id);
        }

        $location->update($validated);

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location "' . $location->name . '" updated.');
    }

    public function destroy(Location $location): RedirectResponse
    {
        $this->authorizeLocation($location);

        $name = $location->name;

        // Prevent deletion if entries exist; disable instead
        if ($location->volunteerEntries()->exists()) {
            return back()->with('error', '"' . $name . '" has existing entries and cannot be deleted. Disable it instead.');
        }

        $location->delete();

        return redirect()->route('admin.locations.index')
            ->with('success', 'Location "' . $name . '" deleted.');
    }

    public function toggleActive(Location $location): mixed
    {
        $this->authorizeLocation($location);

        $location->update(['is_active' => ! $location->is_active]);

        // HTMX partial re-render
        if (request()->header('HX-Request')) {
            return view('admin.locations.partials.row', compact('location'));
        }

        $state = $location->is_active ? 'activated' : 'deactivated';
        return back()->with('success', '"' . $location->name . '" ' . $state . '.');
    }
}
