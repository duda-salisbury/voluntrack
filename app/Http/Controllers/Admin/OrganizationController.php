<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\OrganizationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Super-admin only: create and manage all organizations.
 * Org-admins (organization_id !== null) are blocked at the controller level.
 */
class OrganizationController extends Controller
{
    public function index(): View
    {
        $this->requireSuperAdmin();

        $organizations = Organization::withCount(['locations', 'volunteerEntries'])
            ->orderBy('name')
            ->get();

        return view('admin.organizations.index', compact('organizations'));
    }

    public function create(): View
    {
        $this->requireSuperAdmin();

        $defaults = OrganizationSettings::defaults();

        return view('admin.organizations.create', compact('defaults'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'slug'               => 'required|string|max:100|alpha_dash|unique:organizations,slug',
            'accent_color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active'          => 'nullable|boolean',
            'logo'               => 'nullable|image|max:2048|mimes:jpg,jpeg,png,webp,svg',
            'welcome_title'      => 'required|string|max:255',
            'welcome_message'    => 'required|string|max:500',
            'submit_button_text' => 'required|string|max:100',
            'success_message'    => 'required|string|max:500',
            'label_name'         => 'required|string|max:100',
            'label_location'     => 'required|string|max:100',
            'label_date'         => 'required|string|max:100',
            'label_hours'        => 'required|string|max:100',
            'require_name'       => 'nullable|boolean',
            'show_notes'         => 'nullable|boolean',
            'show_hours_hint'    => 'nullable|boolean',
        ]);

        $settings = $this->buildSettings($validated);

        $org = Organization::create([
            'name'         => $validated['name'],
            'slug'         => $validated['slug'],
            'accent_color' => $validated['accent_color'],
            'is_active'    => (bool) ($validated['is_active'] ?? true),
            'settings'     => $settings->toArray(),
        ]);

        if ($request->hasFile('logo')) {
            $org->update([
                'logo_path' => $request->file('logo')->store('logos', 'public'),
            ]);
        }

        return redirect()->route('admin.organizations.index')
            ->with('success', "Organization \"{$org->name}\" created successfully.");
    }

    public function edit(Organization $organization): View
    {
        $this->requireSuperAdmin();

        return view('admin.organizations.edit', compact('organization'));
    }

    public function update(Request $request, Organization $organization): RedirectResponse
    {
        $this->requireSuperAdmin();

        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'slug'               => "required|string|max:100|alpha_dash|unique:organizations,slug,{$organization->id}",
            'accent_color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active'          => 'nullable|boolean',
            'logo'               => 'nullable|image|max:2048|mimes:jpg,jpeg,png,webp,svg',
            'remove_logo'        => 'nullable|boolean',
            'welcome_title'      => 'required|string|max:255',
            'welcome_message'    => 'required|string|max:500',
            'submit_button_text' => 'required|string|max:100',
            'success_message'    => 'required|string|max:500',
            'label_name'         => 'required|string|max:100',
            'label_location'     => 'required|string|max:100',
            'label_date'         => 'required|string|max:100',
            'label_hours'        => 'required|string|max:100',
            'require_name'       => 'nullable|boolean',
            'show_notes'         => 'nullable|boolean',
            'show_hours_hint'    => 'nullable|boolean',
        ]);

        $settings = $this->buildSettings($validated);

        $update = [
            'name'         => $validated['name'],
            'slug'         => $validated['slug'],
            'accent_color' => $validated['accent_color'],
            'is_active'    => (bool) ($validated['is_active'] ?? false),
            'settings'     => $settings->toArray(),
        ];

        if ($request->boolean('remove_logo') && $organization->logo_path) {
            Storage::disk('public')->delete($organization->logo_path);
            $update['logo_path'] = null;
        }

        if ($request->hasFile('logo')) {
            if ($organization->logo_path) {
                Storage::disk('public')->delete($organization->logo_path);
            }
            $update['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $organization->update($update);

        return redirect()->route('admin.organizations.index')
            ->with('success', "Organization \"{$organization->name}\" updated successfully.");
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->requireSuperAdmin();

        if ($organization->logo_path) {
            Storage::disk('public')->delete($organization->logo_path);
        }

        $name = $organization->name;
        $organization->delete();

        return redirect()->route('admin.organizations.index')
            ->with('success', "\"{$name}\" has been deleted.");
    }

    public function toggleActive(Organization $organization): RedirectResponse
    {
        $this->requireSuperAdmin();

        $organization->update(['is_active' => ! $organization->is_active]);

        $status = $organization->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "\"{$organization->name}\" has been {$status}.");
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function requireSuperAdmin(): void
    {
        if (! auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only super-admins can manage organizations.');
        }
    }

    private function buildSettings(array $validated): OrganizationSettings
    {
        return OrganizationSettings::fromArray([
            'welcome_title'      => $validated['welcome_title'],
            'welcome_message'    => $validated['welcome_message'],
            'submit_button_text' => $validated['submit_button_text'],
            'success_message'    => $validated['success_message'],
            'labels' => [
                'name'     => $validated['label_name'],
                'location' => $validated['label_location'],
                'date'     => $validated['label_date'],
                'hours'    => $validated['label_hours'],
            ],
            'form' => [
                'require_name'    => (bool) ($validated['require_name']    ?? false),
                'show_notes'      => (bool) ($validated['show_notes']      ?? false),
                'show_hours_hint' => (bool) ($validated['show_hours_hint'] ?? true),
            ],
        ]);
    }
}
