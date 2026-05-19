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
        $query = VolunteerEntry::with(['location', 'matchedByAdmin']);

        // Filter by location
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('volunteered_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('volunteered_at', '<=', $request->date_to);
        }

        // Filter by matched status
        if ($request->filled('matched')) {
            $query->where('is_matched', $request->matched === 'yes');
        }

        $entries = $query->orderBy('volunteered_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_entries' => VolunteerEntry::count(),
            'total_hours' => VolunteerEntry::sum('hours'),
            'matched_hours' => VolunteerEntry::where('is_matched', true)->sum('hours'),
            'unmatched_hours' => VolunteerEntry::where('is_matched', false)->sum('hours'),
        ];

        $locations = Location::orderBy('name')->get();

        return view('admin.dashboard', compact('entries', 'stats', 'locations'));
    }
}
