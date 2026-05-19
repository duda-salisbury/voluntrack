<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Shows a logged-in volunteer's own entry history for a given organization.
 */
class HistoryController extends Controller
{
    public function index(Organization $org): View|RedirectResponse
    {
        if (! auth()->check() || auth()->user()->is_admin) {
            return redirect()->route('org.account.login', $org)
                ->with('error', 'Please sign in to view your history.');
        }

        $user = auth()->user();

        $entries = $user->volunteerEntries()
            ->where('organization_id', $org->id)
            ->with('location')
            ->orderBy('volunteered_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $totalHours = $user->volunteerEntries()
            ->where('organization_id', $org->id)
            ->sum('hours');

        return view('hours.account.history', compact('org', 'user', 'entries', 'totalHours'));
    }
}
