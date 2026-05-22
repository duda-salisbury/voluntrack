<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Organization;
use App\Models\User;
use App\Models\VolunteerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EntryController extends Controller
{
    public function create(): View
    {
        $user      = auth()->user();
        $orgId     = $user->organization_id;
        $locations = Location::orderBy('name')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get();

        $organizations = $user->isSuperAdmin()
            ? Organization::orderBy('name')->get(['id', 'name', 'slug'])
            : collect();

        // For org-admins scope volunteers to their org; for superadmin all volunteers are
        // passed as JSON grouped by org and filtered client-side
        $volunteers = $user->isSuperAdmin()
            ? User::where('is_admin', false)->orderBy('name')->get(['id', 'name', 'email', 'organization_id'])
            : User::where('is_admin', false)->where('organization_id', $orgId)->orderBy('name')->get(['id', 'name', 'email', 'organization_id']);

        $locationsJson  = $locations->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'org' => $l->organization_id])->values();
        $volunteersJson = $volunteers->map(fn ($v) => ['id' => $v->id, 'name' => $v->name, 'email' => $v->email, 'org' => $v->organization_id])->values();

        return view('admin.entries.create', compact('locations', 'organizations', 'volunteers', 'locationsJson', 'volunteersJson'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user         = auth()->user();
        $orgId        = $user->organization_id;

        // Superadmin picks org from the form
        if ($user->isSuperAdmin()) {
            $request->validate(['organization_id' => 'required|exists:organizations,id']);
            $orgId = $request->input('organization_id');
        }

        $locationRule = "required|exists:locations,id,organization_id,{$orgId}";

        $validated = $request->validate([
            'name'               => 'nullable|string|max:255',
            'volunteer_user_id'  => 'nullable|exists:users,id',
            'location_id'        => $locationRule,
            'volunteered_at'     => 'required|date',
            'hours'              => 'required|numeric|min:0.01|max:24',
            'is_matched'         => 'boolean',
            'notes'              => 'nullable|string',
        ]);

        // If a registered volunteer was selected, use their name if none provided
        if (!empty($validated['volunteer_user_id']) && empty($validated['name'])) {
            $volunteerUser = User::find($validated['volunteer_user_id']);
            if ($volunteerUser) $validated['name'] = $volunteerUser->name;
        }

        $validated['source']          = 'admin';
        $validated['organization_id'] = $orgId;

        if ($validated['is_matched'] ?? false) {
            $validated['matched_by_admin_id'] = auth()->id();
        }

        VolunteerEntry::create($validated);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Entry created successfully.');
    }

    public function edit(VolunteerEntry $entry): View
    {
        $orgId     = auth()->user()->organization_id;
        $locations = Location::orderBy('name')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get();

        return view('admin.entries.edit', compact('entry', 'locations'));
    }

    public function update(Request $request, VolunteerEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'volunteered_at' => 'required|date',
            'hours' => 'required|numeric|min:0.01|max:24',
            'is_matched' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        if (($validated['is_matched'] ?? false) && !$entry->is_matched) {
            $validated['matched_by_admin_id'] = auth()->id();
        } elseif (!($validated['is_matched'] ?? false)) {
            $validated['matched_by_admin_id'] = null;
        }

        $entry->update($validated);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Entry updated successfully.');
    }

    public function destroy(VolunteerEntry $entry): RedirectResponse
    {
        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            abort_if($entry->organization_id !== $user->organization_id, 403);
        }

        $entry->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Entry deleted successfully.');
    }

    public function updateInline(Request $request, VolunteerEntry $entry)
    {
        $field = $request->input('field');

        match ($field) {
            'hours' => (function () use ($request, $entry) {
                $request->validate(['value' => 'required|numeric|min:0.01|max:24']);
                $entry->update(['hours' => $request->input('value')]);
            })(),
            'is_matched' => (function () use ($request, $entry) {
                $val = filter_var($request->input('value'), FILTER_VALIDATE_BOOLEAN);
                $entry->is_matched = $val;
                $entry->matched_by_admin_id = $val ? auth()->id() : null;
                $entry->save();
            })(),
            'notes' => (function () use ($request, $entry) {
                $request->validate(['value' => 'nullable|string|max:2000']);
                $entry->update(['notes' => $request->input('value')]);
            })(),
            default => abort(422, 'Unknown field'),
        };

        $entry->load(['location', 'matchedByAdmin', 'organization']);
        return response(view('admin.partials.entry-row', ['entry' => $entry, 'justSaved' => true]))
            ->header('HX-Trigger', 'statsUpdated');
    }

    public function toggleMatch(Request $request, VolunteerEntry $entry)
    {
        $entry->is_matched = !$entry->is_matched;
        $entry->matched_by_admin_id = $entry->is_matched ? auth()->id() : null;
        $entry->save();

        if ($request->header('HX-Request')) {
            return view('admin.partials.entry-row', compact('entry'));
        }

        return back()->with('success', 'Match status updated.');
    }

    public function updateNotes(Request $request, VolunteerEntry $entry)
    {
        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $entry->update(['notes' => $request->notes]);

        if ($request->header('HX-Request')) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Notes updated.');
    }
}
