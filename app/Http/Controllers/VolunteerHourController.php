<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Organization;
use App\Models\VolunteerEntry;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class VolunteerHourController extends Controller
{
    /**
     * Show the public volunteer hour logging form for a specific organization.
     */
    public function index(Organization $org, Request $request): View
    {
        $locations = Location::where('organization_id', $org->id)
            ->active()
            ->orderBy('name')
            ->get();

        if ($request->header('HX-Request')) {
            return view('hours.log', compact('org', 'locations') + ['htmxPartial' => true]);
        }

        return view('hours.log', compact('org', 'locations'));
    }

    /**
     * Store a new volunteer hour entry for the organization.
     */
    public function store(Organization $org, Request $request): View|RedirectResponse
    {
        $config = $org->config;

        $rules = [
            'name'           => ($config->requireName ? 'required' : 'nullable') . '|string|max:255',
            'location_id'    => "required|exists:locations,id,organization_id,{$org->id}",
            'volunteered_at' => 'required|date|before_or_equal:today',
            'hours'          => 'required|numeric|min:0.01|max:24',
            // Anonymous continuity token — client-generated UUID, never required
            'anon_token'     => 'nullable|uuid',
        ];

        if ($config->showNotes) {
            $rules['notes'] = 'nullable|string|max:1000';
        }

        $validated = $request->validate($rules, [
            'name.required'                  => 'Please enter your name.',
            'location_id.required'           => "Please select a {$config->labelLocation}.",
            'location_id.exists'             => "Invalid {$config->labelLocation} selected.",
            'volunteered_at.required'        => 'Please enter the date you volunteered.',
            'volunteered_at.date'            => 'Please enter a valid date.',
            'volunteered_at.before_or_equal' => 'Date cannot be in the future.',
            'hours.required'                 => "Please enter the number of {$config->labelHours}.",
            'hours.numeric'                  => "{$config->labelHours} must be a number.",
            'hours.min'                      => "{$config->labelHours} must be at least 0.01.",
            'hours.max'                      => "{$config->labelHours} cannot exceed 24 in a single entry.",
        ]);

        $validated['source']          = 'manual';
        $validated['organization_id'] = $org->id;

        // Link to volunteer account if one is logged in
        if (auth()->check() && auth()->user()->isVolunteer()) {
            $validated['volunteer_user_id'] = auth()->id();
            if (empty($validated['name'])) {
                $validated['name'] = auth()->user()->name;
            }
            // Registered users don't need the anonymous token
            unset($validated['anon_token']);
        }

        $entry = VolunteerEntry::create($validated);

        if ($request->header('HX-Request')) {
            return view('hours.log', [
                'org'         => $org,
                'showSuccess' => true,
                'entry'       => $entry,
                'htmxPartial' => true,
            ]);
        }

        return redirect()->route('org.hours.success', $org)
            ->with('success', $config->successMessage);
    }

    /**
     * Show the success confirmation page (non-HTMX fallback).
     */
    public function success(Organization $org): View
    {
        return view('hours.success', compact('org'));
    }
}
