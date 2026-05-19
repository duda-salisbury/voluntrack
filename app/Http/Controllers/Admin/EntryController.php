<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\VolunteerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class EntryController extends Controller
{
    public function create(): View
    {
        $orgId     = auth()->user()->organization_id;
        $locations = Location::orderBy('name')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get();

        return view('admin.entries.create', compact('locations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId        = auth()->user()->organization_id;
        $locationRule = $orgId
            ? "required|exists:locations,id,organization_id,{$orgId}"
            : 'required|exists:locations,id';

        $validated = $request->validate([
            'name'           => 'nullable|string|max:255',
            'location_id'    => $locationRule,
            'volunteered_at' => 'required|date',
            'hours'          => 'required|numeric|min:0.01|max:24',
            'is_matched'     => 'boolean',
            'notes'          => 'nullable|string',
        ]);

        $validated['source'] = 'admin';

        if ($orgId !== null) {
            $validated['organization_id'] = $orgId;
        }

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
        $entry->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Entry deleted successfully.');
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
