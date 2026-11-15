<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Models\User;
use App\Models\VolunteerEntry;
use App\Rules\EventLocation;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EntryController extends Controller
{
    public function createGroupTotal(): View
    {
        $orgId = auth()->user()->adminOrganizationId();
        abort_if($orgId === null, 403, 'Group totals are available in an organization workspace.');

        return $this->groupTotalForm($orgId);
    }

    public function storeGroupTotal(Request $request): RedirectResponse
    {
        $orgId = $request->user()->adminOrganizationId();
        abort_if($orgId === null, 403, 'Group totals are available in an organization workspace.');
        $data = $this->validateGroupTotal($request, $orgId);
        $data['organization_id'] = $orgId;
        $data['source'] = 'admin';
        $data['is_group_total'] = true;
        $data['created_by_admin_id'] = $request->user()->id;
        $data['is_matched'] = $request->boolean('is_matched');
        $data['matched_by_admin_id'] = $data['is_matched'] ? $request->user()->id : null;
        VolunteerEntry::create($data);

        return redirect()->route('admin.dashboard')->with('success', 'Group total recorded.');
    }

    private function groupTotalForm(int $orgId, ?VolunteerEntry $entry = null): View
    {
        $locations = Location::withTrashed()->where('organization_id', $orgId)
            ->where(fn ($query) => $query->whereNull('deleted_at')
                ->when($entry, fn ($query) => $query->orWhere('id', $entry->location_id)))
            ->orderBy('name')->get();
        $events = SpecialEvent::with('locations')->where('organization_id', $orgId)->orderByDesc('starts_on')->get();

        return view('admin.entries.group-total', compact('entry', 'locations', 'events'));
    }

    private function validateGroupTotal(Request $request, int $orgId, ?VolunteerEntry $entry = null): array
    {
        return $request->validate([
            'location_id' => ['bail', 'required', 'integer', Rule::exists('locations', 'id')->where(fn ($query) => $query
                ->where('organization_id', $orgId)->where(fn ($query) => $query->whereNull('deleted_at')
                    ->when($entry, fn ($query) => $query->orWhere('id', $entry->location_id))))],
            'special_event_id' => ['bail', 'nullable', 'integer', Rule::exists('special_events', 'id')->where('organization_id', $orgId)],
            'volunteered_at' => 'required|date',
            'hours' => 'required|numeric|decimal:0,2|min:0.01|max:'.VolunteerEntry::MAX_GROUP_HOURS,
            'participant_count' => 'nullable|integer|min:1|max:'.VolunteerEntry::MAX_GROUP_PARTICIPANTS,
            'is_matched' => 'boolean',
            'notes' => 'nullable|string|max:2000',
        ]);
    }

    public function createGroup(): View
    {
        $orgId = auth()->user()->adminOrganizationId();
        abort_if($orgId === null, 403, 'Group entry is available in an organization workspace.');
        $locations = Location::where('organization_id', $orgId)->orderBy('name')->get();
        $events = SpecialEvent::with('locations')->where('organization_id', $orgId)->orderByDesc('starts_on')->get();

        return view('admin.entries.group', compact('locations', 'events'));
    }

    public function storeGroup(Request $request): RedirectResponse
    {
        $orgId = auth()->user()->adminOrganizationId();
        abort_if($orgId === null, 403, 'Group entry is available in an organization workspace.');
        $validator = Validator::make($request->all(), [
            'location_id' => ['bail', 'required', 'integer', Rule::exists('locations', 'id')->where('organization_id', $orgId)->whereNull('deleted_at'), new EventLocation($request->input('special_event_id'), $orgId)],
            'special_event_id' => ['bail', 'nullable', 'integer', Rule::exists('special_events', 'id')->where('organization_id', $orgId)],
            'volunteered_at' => 'required|date',
            'rows' => 'required|array|min:1|max:50',
            'rows.*.name' => 'required|string|max:255',
            'rows.*.hours' => 'nullable|integer|min:0|max:24',
            'rows.*.minutes' => 'nullable|integer|min:0|max:59',
        ]);
        $validator->after(function ($validator) use ($request) {
            if ($validator->errors()->isNotEmpty() || ! is_array($request->input('rows'))) {
                return;
            }
            foreach ($request->input('rows') as $index => $row) {
                $minutes = (int) ($row['hours'] ?? 0) * 60 + (int) ($row['minutes'] ?? 0);
                if ($minutes < 1 || $minutes > 1440) {
                    $validator->errors()->add("rows.$index.hours", 'Each duration must be between 1 minute and 24 hours.');
                }
            }
        });
        $data = $validator->validate();

        DB::transaction(function () use ($data, $orgId) {
            foreach ($data['rows'] as $row) {
                VolunteerEntry::create([
                    'organization_id' => $orgId,
                    'location_id' => $data['location_id'],
                    'special_event_id' => $data['special_event_id'] ?? null,
                    'volunteered_at' => $data['volunteered_at'],
                    'name' => $row['name'],
                    'hours' => round((int) ($row['hours'] ?? 0) + (int) ($row['minutes'] ?? 0) / 60, 2),
                    'source' => 'admin',
                ]);
            }
        });

        return redirect()->route('admin.dashboard')->with('success', count($data['rows']).' contributions recorded.');
    }

    private function authorizeEntry(VolunteerEntry $entry): void
    {
        $user = auth()->user();
        abort_unless($user->isPlatformWorkspace() || $entry->organization_id === $user->adminOrganizationId(), 403);
    }

    public function create(): View
    {
        $user      = auth()->user();
        $orgId     = $user->adminOrganizationId();
        $locations = Location::orderBy('name')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get();

        $organizations = $user->isPlatformWorkspace()
            ? Organization::orderBy('name')->get(['id', 'name', 'slug'])
            : collect();

        // For org-admins scope volunteers to their org; for superadmin all volunteers are
        // passed as JSON grouped by org and filtered client-side
        $volunteers = User::with(['organizations' => fn ($q) => $q->when($orgId, fn ($q) => $q->where('organizations.id', $orgId))])
            ->when($orgId, fn ($q) => $q->whereHas('organizations', fn ($q) => $q->where('organizations.id', $orgId)))->orderBy('name')->get();

        $locationsJson  = $locations->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'org' => $l->organization_id])->values();
        $volunteersJson = $volunteers->flatMap(fn ($v) => $v->organizations->map(fn ($org) => ['id' => $v->id, 'name' => $v->name, 'email' => $v->email, 'org' => $org->id]))->values();
        $events = SpecialEvent::with(['organization', 'locations'])->orderBy('name')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))->get();

        return view('admin.entries.create', compact('locations', 'organizations', 'volunteers', 'locationsJson', 'volunteersJson', 'events'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user         = auth()->user();
        $orgId        = $user->adminOrganizationId();

        // Superadmin picks org from the form
        if ($user->isPlatformWorkspace()) {
            $request->validate(['organization_id' => 'required|exists:organizations,id']);
            $orgId = $request->input('organization_id');
        }

        $validated = $request->validate([
            'name'               => 'nullable|string|max:255',
            'volunteer_user_id'  => ['nullable', Rule::exists('users', 'id')->whereNull('deleted_at')->where(fn ($query) => $query
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('organization_user')->whereColumn('organization_user.user_id', 'users.id')->where('organization_user.organization_id', $orgId)))],
            'location_id'        => ['bail', 'required', 'integer', Rule::exists('locations', 'id')->where('organization_id', $orgId)->whereNull('deleted_at'), new EventLocation($request->input('special_event_id'), (int) $orgId)],
            'special_event_id'   => ['bail', 'nullable', 'integer', Rule::exists('special_events', 'id')->where('organization_id', $orgId)],
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
        if ($entry->is_group_total) {
            return $this->groupTotalForm($orgId, $entry);
        }
        $locations = Location::orderBy('name')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get();

        $events = SpecialEvent::with('locations')->where('organization_id', $orgId)->orderBy('name')->get();

        return view('admin.entries.edit', compact('entry', 'locations', 'events'));
    }

    public function update(Request $request, VolunteerEntry $entry): RedirectResponse
    {
        $this->authorizeEntry($entry);
        if ($entry->is_group_total) {
            $validated = $this->validateGroupTotal($request, $entry->organization_id, $entry);
            $validated['is_matched'] = $request->boolean('is_matched');
            if ($validated['is_matched'] && ! $entry->is_matched) {
                $validated['matched_by_admin_id'] = auth()->id();
            } elseif (! $validated['is_matched']) {
                $validated['matched_by_admin_id'] = null;
            }
            $entry->update($validated);

            return redirect()->route('admin.dashboard')->with('success', 'Group total updated.');
        }
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'location_id' => ['bail', 'required', 'integer', Rule::exists('locations', 'id')->where('organization_id', $entry->organization_id)->whereNull('deleted_at'), new EventLocation($request->input('special_event_id'), $entry->organization_id, $entry)],
            'special_event_id' => ['bail', 'nullable', 'integer', Rule::exists('special_events', 'id')->where('organization_id', $entry->organization_id)],
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
        if (! $user->isPlatformWorkspace()) {
            abort_if($entry->organization_id !== $user->adminOrganizationId(), 403);
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
                $rule = $entry->is_group_total
                    ? 'required|numeric|decimal:0,2|min:0.01|max:'.VolunteerEntry::MAX_GROUP_HOURS
                    : 'required|numeric|min:0.01|max:24';
                $request->validate(['value' => $rule]);
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

        $entry->load(['location', 'specialEvent', 'matchedByAdmin', 'createdByAdmin', 'organization']);
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
