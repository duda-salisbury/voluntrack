<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Organization;
use App\Models\VolunteerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user  = auth()->user();
        $orgId = $user->organization_id;

        $query = VolunteerEntry::with(['location', 'matchedByAdmin', 'organization']);

        if ($orgId !== null) {
            $query->where('organization_id', $orgId);
        }

        // Filters
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('volunteered_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('volunteered_at', '<=', $request->date_to);
        }
        if ($request->filled('matched')) {
            $query->where('is_matched', $request->matched === 'yes');
        }
        if ($orgId === null && $request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }

        $entries = $query->orderBy('volunteered_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats     = $this->buildStats($orgId);
        $locations = $this->buildLocations($orgId);

        $organizations = $orgId === null
            ? Organization::orderBy('name')->get(['id', 'name', 'slug'])
            : collect();

        return view('admin.dashboard', compact('entries', 'stats', 'locations', 'organizations'));
    }

    public function stats(): \Illuminate\Http\Response
    {
        $orgId = auth()->user()->organization_id;
        $stats = $this->buildStats($orgId);
        return response(view('admin.partials.stats-cards', compact('stats')));
    }

    private function buildStats(?int $orgId): array
    {
        $base = VolunteerEntry::query();
        if ($orgId !== null) {
            $base->where('organization_id', $orgId);
        }
        return [
            'total_entries'   => (clone $base)->count(),
            'total_hours'     => (clone $base)->sum('hours'),
            'matched_hours'   => (clone $base)->where('is_matched', true)->sum('hours'),
            'unmatched_hours' => (clone $base)->where('is_matched', false)->sum('hours'),
        ];
    }

    private function buildLocations(?int $orgId)
    {
        return Location::orderBy('name')
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->get();
    }
}
