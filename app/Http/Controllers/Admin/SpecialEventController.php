<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\SpecialEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SpecialEventController extends Controller
{
    private function authorizeEvent(SpecialEvent $specialEvent): void
    {
        abort_unless(auth()->user()->isSuperAdmin()
            || $specialEvent->organization_id === auth()->user()->organization_id, 403);
    }

    public function index(Request $request): View
    {
        $orgId = auth()->user()->organization_id;
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
        $event = SpecialEvent::create($this->validated($request));

        return redirect()->route('admin.special-events.show', $event)
            ->with('success', 'Special event created. Share its link or start recording hours.');
    }

    public function show(SpecialEvent $specialEvent): View
    {
        $this->authorizeEvent($specialEvent);
        $specialEvent->load('organization')->loadCount('volunteerEntries')->loadSum('volunteerEntries', 'hours');

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
        $data = $this->validated($request);
        // Moving an event would detach its existing entries from their organization.
        $data['organization_id'] = $specialEvent->organization_id;
        $specialEvent->update($data);

        return redirect()->route('admin.special-events.show', $specialEvent)
            ->with('success', 'Special event saved. Existing hours are preserved.');
    }

    private function form(SpecialEvent $event): View
    {
        $organizations = auth()->user()->isSuperAdmin()
            ? Organization::orderBy('name')->get()
            : collect();

        return view('admin.events.form', compact('event', 'organizations'));
    }

    private function validated(Request $request): array
    {
        $orgId = auth()->user()->organization_id;
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

        return $data;
    }
}
