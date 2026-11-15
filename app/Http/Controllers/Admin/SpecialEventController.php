<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SpecialEventController extends Controller
{
    private function authorizeEvent(SpecialEvent $specialEvent): void
    {
        abort_unless(auth()->user()->isPlatformWorkspace()
            || $specialEvent->organization_id === auth()->user()->adminOrganizationId(), 403);
    }

    public function index(Request $request): View
    {
        $orgId = auth()->user()->adminOrganizationId();
        $events = SpecialEvent::with('organization')->withCount('volunteerEntries')
            ->withSum('volunteerEntries', 'hours')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->when($orgId === null && $request->filled('organization_id'), fn ($q) => $q->where('organization_id', $request->input('organization_id')))
            ->orderByDesc('starts_on')->paginate(20)->withQueryString();

        return view('admin.events.index', compact('events'));
    }

    public function create(): View
    {
        return $this->form(new SpecialEvent(['is_active' => true]));
    }

    public function store(Request $request): RedirectResponse
    {
        [$data, $locationIds] = $this->validated($request);
        $event = DB::transaction(function () use ($data, $locationIds) {
            $event = SpecialEvent::create($data);
            $event->locations()->sync($locationIds);

            return $event;
        });

        return redirect()->route('admin.special-events.show', $event)
            ->with('success', 'Special event created. Share its link or start recording hours.');
    }

    public function show(SpecialEvent $specialEvent): View
    {
        $this->authorizeEvent($specialEvent);
        $specialEvent->load(['organization', 'locations'])->loadCount('volunteerEntries')->loadSum('volunteerEntries', 'hours');

        return view('admin.events.show', ['event' => $specialEvent]);
    }

    public function edit(SpecialEvent $specialEvent): View
    {
        $this->authorizeEvent($specialEvent);

        return $this->form($specialEvent);
    }

    public function update(Request $request, SpecialEvent $specialEvent): RedirectResponse
    {
        $this->authorizeEvent($specialEvent);
        [$data, $locationIds] = $this->validated($request);
        // Moving an event would detach its existing entries from their organization.
        $data['organization_id'] = $specialEvent->organization_id;
        DB::transaction(function () use ($specialEvent, $data, $locationIds) {
            $specialEvent->update($data);
            $changes = $specialEvent->locations()->sync($locationIds);
            if (! $specialEvent->wasChanged() && ($changes['attached'] || $changes['detached'])) {
                Audit::record('event.updated', 'event', 'Updated event locations', actorId: auth()->id(),
                    organizationId: $specialEvent->organization_id, subjectType: 'event', subjectId: $specialEvent->id,
                    metadata: ['changes' => ['Event locations']]);
            }
        });

        return redirect()->route('admin.special-events.show', $specialEvent)
            ->with('success', 'Special event saved. Existing hours are preserved.');
    }

    private function form(SpecialEvent $event): View
    {
        $organizations = auth()->user()->isPlatformWorkspace()
            ? Organization::orderBy('name')->get()
            : collect();
        $orgId = $event->exists ? $event->organization_id : auth()->user()->adminOrganizationId();
        $locations = Location::orderBy('name')->when($orgId, fn ($query) => $query->where('organization_id', $orgId))->get();
        $event->loadMissing('locations');

        return view('admin.events.form', compact('event', 'organizations', 'locations'));
    }

    private function validated(Request $request): array
    {
        $orgId = auth()->user()->adminOrganizationId();
        $data = $request->validate([
            'organization_id' => $orgId === null && ! $request->route('special_event')
                ? 'required|exists:organizations,id,deleted_at,NULL'
                : 'nullable',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'starts_on' => 'required|date',
            'ends_on' => 'nullable|date|after_or_equal:starts_on',
            'is_active' => 'required|boolean',
        ]);
        $data['organization_id'] = $orgId ?? $request->route('special_event')?->organization_id ?? $data['organization_id'];

        $locationData = $request->validate([
            'location_mode' => ['nullable', Rule::in(['any', 'selected'])],
            'location_ids' => ['exclude_unless:location_mode,selected', 'required', 'array', 'min:1'],
            'location_ids.*' => ['bail', 'integer', 'distinct', Rule::exists('locations', 'id')
                ->where('organization_id', $data['organization_id'])->whereNull('deleted_at')],
        ]);

        return [$data, ($locationData['location_mode'] ?? 'any') === 'selected' ? $locationData['location_ids'] : []];
    }
}
