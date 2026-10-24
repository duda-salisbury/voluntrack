<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Models\User;
use App\Models\VolunteerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class EntryController extends Controller
{
    private function authorizeEntry(VolunteerEntry $entry): void
    {
        $user = auth()->user();
        abort_unless($user->isSuperAdmin() || $entry->organization_id === $user->organization_id, 403);
    }

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
        $events = SpecialEvent::with('organization')->orderBy('name')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))->get();

        return view('admin.entries.create', compact('locations', 'organizations', 'volunteers', 'locationsJson', 'volunteersJson', 'events'));
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

        $validated = $request->validate([
            'name'               => 'nullable|string|max:255',
            'volunteer_user_id'  => ['nullable', Rule::exists('users', 'id')->where('organization_id', $orgId)->where('is_admin', false)->whereNull('deleted_at')],
            'location_id'        => ['required', Rule::exists('locations', 'id')->where('organization_id', $orgId)->whereNull('deleted_at')],
            'special_event_id'   => ['nullable', Rule::exists('special_events', 'id')->where('organization_id', $orgId)],
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
        $this->authorizeEntry($entry);
        $orgId = $entry->organization_id;
        $locations = Location::orderBy('name')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get();

        $events = SpecialEvent::where('organization_id', $orgId)->orderBy('name')->get();

        return view('admin.entries.edit', compact('entry', 'locations', 'events'));
    }

    public function update(Request $request, VolunteerEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($entry);
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'location_id' => ['required', Rule::exists('locations', 'id')->where('organization_id', $entry->organization_id)->whereNull('deleted_at')],
            'special_event_id' => ['nullable', Rule::exists('special_events', 'id')->where('organization_id', $entry->organization_id)],
            'volunteered_at' => 'required|date',
            'hours' => 'required|numeric|min:0.01|max:24',
            'is_matched' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        $validated['is_matched'] = $request->boolean('is_matched');

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
        $this->authorizeEntry($entry);
        $field = $request->input('field');

        match ($field) {
            'hours' => (function () use ($request, $entry) {
                $request->validate(['value' => 'required|numeric|min:0.01|max:24']);
                $entry->update(['hours' => $request->input('value')]);
            })(),
            'is_matched' => (function () use ($request, $entry) {
                $request->validate(['value' => ['required', Rule::in(['true', 'false', '1', '0'])]]);
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

        $entry->load(['location', 'specialEvent', 'matchedByAdmin', 'organization']);
        return response(view('admin.partials.entry-row', ['entry' => $entry, 'justSaved' => true]))
            ->header('HX-Trigger', 'statsUpdated');
    }

    public function toggleMatch(Request $request, VolunteerEntry $entry)
    {
        $this->authorizeEntry($entry);
        $entry->is_matched = !$entry->is_matched;
        $entry->matched_by_admin_id = $entry->is_matched ? auth()->id() : null;
        $entry->save();

        if ($request->header('HX-Request')) {
            return response(view('admin.partials.entry-row', compact('entry')))
                ->header('HX-Trigger', 'statsUpdated');
        }

        return back()->with('success', 'Match status updated.');
    }

    public function updateNotes(Request $request, VolunteerEntry $entry)
    {
        $this->authorizeEntry($entry);
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
