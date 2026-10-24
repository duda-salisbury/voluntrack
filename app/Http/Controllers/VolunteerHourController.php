<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Organization;
use App\Models\VolunteerEntry;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
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
        abort_if(! $org->is_active, 404);

        $locations = Location::where('organization_id', $org->id)
            ->active()
            ->orderBy('name')
            ->get();
        $events = $org->specialEvents()->where('is_active', true)->orderByDesc('starts_on')->get();
        abort_if($request->filled('event') && ! $events->contains(fn ($event) => (string) $event->id === $request->input('event')),
            404, 'This special event is unavailable. Open the organization logging form without an event to record ordinary volunteering.');

        return view('hours.log', compact('org', 'locations', 'events'));
    }

    /**
     * Store a new volunteer hour entry for the organization.
     */
    public function store(Organization $org, Request $request): RedirectResponse
    {
        abort_if(! $org->is_active, 404);
        $config = $org->config;

        $rules = [
            'name'           => ($config->requireName ? 'required' : 'nullable') . '|string|max:255',
            'location_id'    => ['required', Rule::exists('locations', 'id')->where('organization_id', $org->id)->where('is_active', true)->whereNull('deleted_at')],
            'special_event_id' => ['nullable', Rule::exists('special_events', 'id')->where('organization_id', $org->id)->where('is_active', true)],
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

        // Link to volunteer account only if logged in AND belongs to this org
        $isOrgVolunteer = auth()->check()
            && auth()->user()->isVolunteer()
            && (int) auth()->user()->organization_id === (int) $org->id;

        if ($isOrgVolunteer) {
            $validated['volunteer_user_id'] = auth()->id();
            if (empty($validated['name'])) {
                $validated['name'] = auth()->user()->name;
            }
            // Registered users don't need the anonymous token
            unset($validated['anon_token']);
        }

        $entry = VolunteerEntry::create($validated);

        return redirect()->route('org.hours.success', $org)
            ->with('submitted_entry_id', $entry->id);
    }

    /**
     * Show the success confirmation page (non-HTMX fallback).
     */
    public function success(Organization $org): View|RedirectResponse
    {
        abort_if(! $org->is_active, 404);
        $entry = $org->volunteerEntries()->with(['location', 'specialEvent'])
            ->find(session('submitted_entry_id'));

        if (! $entry) {
            return redirect()->route('org.hours.index', $org)
                ->with('error', 'That confirmation is no longer available. If you already submitted hours, your saved contribution is unchanged.');
        }

        return view('hours.success', compact('org', 'entry'));
    }

    public function manifest(Organization $org): JsonResponse
    {
        abort_if(! $org->is_active, 404);

        return response()->json([
            'id' => route('org.hours.index', $org),
            'name' => $org->name.' Volunteer Hours',
            'short_name' => $org->name,
            'start_url' => route('org.hours.index', $org),
            'scope' => url('/'.$org->slug).'/',
            'display' => 'standalone',
            'background_color' => '#f5f7f5',
            'theme_color' => $org->accent_color,
            'icons' => [['src' => url('/voluntrack.svg'), 'sizes' => 'any', 'type' => 'image/svg+xml', 'purpose' => 'any']],
        ])->header('Content-Type', 'application/manifest+json');
    }
}
