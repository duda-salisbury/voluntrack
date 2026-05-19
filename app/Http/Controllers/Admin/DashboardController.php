<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\VolunteerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user  = auth()->user();
        $orgId = $user->organization_id; // null = super-admin (no scope)

        $query = VolunteerEntry::with(['location', 'matchedByAdmin']);

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

        $entries = $query->orderBy('volunteered_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Stats scoped to the same org
        $statsBase = VolunteerEntry::query();
        if ($orgId !== null) {
            $statsBase->where('organization_id', $orgId);
        }

        $stats = [
            'total_entries'  => (clone $statsBase)->count(),
            'total_hours'    => (clone $statsBase)->sum('hours'),
            'matched_hours'  => (clone $statsBase)->where('is_matched', true)->sum('hours'),
            'unmatched_hours'=> (clone $statsBase)->where('is_matched', false)->sum('hours'),
        ];

        // Location dropdown scoped to org (active only for the filter — all for the select)
        $locationsQuery = Location::orderBy('name');
        if ($orgId !== null) {
            $locationsQuery->where('organization_id', $orgId);
        }
        $locations = $locationsQuery->get();

        return view('admin.dashboard', compact('entries', 'stats', 'locations'));
    }
}
