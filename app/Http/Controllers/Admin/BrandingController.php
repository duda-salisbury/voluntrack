<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Support\OrganizationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Allows an org-admin to edit the branding and settings of their own
 * organization.  Super-admins (organization_id = null) edit branding
 * via the OrganizationController instead.
 */
class BrandingController extends Controller
{
    public function edit(): View|RedirectResponse
    {
        $org = $this->resolveOrg();

        if (! $org) {
            return redirect()->route('admin.organizations.index')
                ->with('error', 'Select an organization to manage its branding.');
        }

        return view('admin.branding.edit', compact('org'));
    }

    public function update(Request $request): RedirectResponse
    {
        $org = $this->resolveOrg();

        if (! $org) {
            abort(403);
        }

        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'accent_color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
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

        $settings = OrganizationSettings::fromArray([
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

        $update = [
            'name'         => $validated['name'],
            'accent_color' => $validated['accent_color'],
            'settings'     => $settings->toArray(),
        ];

        // Handle logo removal
        if ($request->boolean('remove_logo') && $org->logo_path) {
            Storage::disk('public')->delete($org->logo_path);
            $update['logo_path'] = null;
        }

        // Handle logo upload (takes precedence over remove_logo)
        if ($request->hasFile('logo')) {
            if ($org->logo_path) {
                Storage::disk('public')->delete($org->logo_path);
            }
            $update['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $org->update($update);

        return redirect()->route('admin.branding.edit')
            ->with('success', 'Branding settings saved successfully.');
    }

    // -------------------------------------------------------------------------

    private function resolveOrg(): ?Organization
    {
        return auth()->user()?->organization;
    }
}
