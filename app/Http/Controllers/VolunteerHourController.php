<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\VolunteerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class VolunteerHourController extends Controller
{
    /**
     * Show the public volunteer hour logging form.
     */
    public function index(Request $request): View
    {
        $locations = Location::orderBy('name')->get();
        
        // If HTMX request, return just the form partial
        if ($request->header('HX-Request')) {
            return view('hours.log', [
                'locations' => $locations,
                'htmxPartial' => true,
            ]);
        }
        
        // Otherwise return full page
        return view('hours.log', [
            'locations' => $locations,
        ]);
    }

    /**
     * Store a new volunteer hour entry.
     */
    public function store(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'volunteered_at' => 'required|date|before_or_equal:today',
            'hours' => 'required|numeric|min:0.01|max:24',
        ], [
            'location_id.required' => 'Please select a location.',
            'location_id.exists' => 'Invalid location selected.',
            'volunteered_at.required' => 'Please enter the date you volunteered.',
            'volunteered_at.date' => 'Please enter a valid date.',
            'volunteered_at.before_or_equal' => 'Date cannot be in the future.',
            'hours.required' => 'Please enter the number of hours.',
            'hours.numeric' => 'Hours must be a number.',
            'hours.min' => 'Hours must be at least 0.01.',
            'hours.max' => 'Hours cannot exceed 24 in a single entry.',
        ]);

        // Set source as 'manual' for public form submissions
        $validated['source'] = 'manual';

        $entry = VolunteerEntry::create($validated);

        // If HTMX request, return success partial
        if ($request->header('HX-Request')) {
            return view('hours.log', [
                'showSuccess' => true,
                'entry' => $entry,
                'htmxPartial' => true,
            ]);
        }

        // Otherwise traditional redirect
        return redirect()->route('hours.success')
            ->with('success', 'Thank you! Your volunteer hours have been recorded.');
    }

    /**
     * Show the success confirmation page (fallback for non-HTMX).
     */
    public function success(): View
    {
        return view('hours.success');
    }
}
