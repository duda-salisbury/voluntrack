<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\AdministratorInvitation;
use App\Support\OrganizationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Super-admin only: create and manage all organizations.
 * Org-admins (organization_id !== null) are blocked at the controller level.
 */
class OrganizationController extends Controller
{
    public function show(Organization $organization): View
    {
        $this->requireSuperAdmin();
        $organization->loadCount(['locations', 'volunteerEntries', 'specialEvents']);
        $administrators = $organization->users()->where('is_admin', true)->orderBy('name')->get();

        return view('admin.organizations.show', compact('organization', 'administrators'));
    }

    public function inviteAdministrator(Request $request, Organization $organization): RedirectResponse
    {
        $this->requireSuperAdmin();
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);
        $user = User::withTrashed()->where('email', $validated['email'])->first();

        if ($user && ($user->trashed()
            || ($user->organization_id !== null && $user->organization_id !== $organization->id)
            || ($user->is_admin && $user->organization_id === null))) {
            return back()->withErrors(['email' => 'This account belongs to another organization or is a platform administrator. Existing access has not been changed.'])->withInput();
        }

        if (! $user) {
            $user = User::create([
                ...$validated,
                'password' => Str::random(64),
                'is_admin' => true,
                'organization_id' => $organization->id,
            ]);
        } else {
            $user->update(['is_admin' => true, 'organization_id' => $organization->id]);
        }

        $status = Password::sendResetLink(
            ['email' => $user->email],
            fn ($recipient, $token) => $recipient->notify(new AdministratorInvitation($token, $organization->name))
        );

        if ($status !== Password::RESET_LINK_SENT) {
            return back()->withErrors(['email' => __($status)])->withInput();
        }

        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', "Administrator assigned. A secure password link was sent to {$user->email}.");
    }

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
        $defaults = OrganizationSettings::defaults();
        $request->mergeIfMissing([
            'accent_color' => '#0d6efd',
            'welcome_title' => $defaults->welcomeTitle,
            'welcome_message' => $defaults->welcomeMessage,
            'submit_button_text' => $defaults->submitButtonText,
            'success_message' => $defaults->successMessage,
            'label_name' => $defaults->labelName,
            'label_location' => $defaults->labelLocation,
            'label_date' => $defaults->labelDate,
            'label_hours' => $defaults->labelHours,
        ]);

        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'slug'               => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', Rule::notIn(['admin', 'login', 'logout', 'account', 'build', 'storage']), 'unique:organizations,slug'],
            'accent_color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active'          => 'nullable|boolean',
            'logo'               => 'nullable|image|max:2048|mimes:jpg,jpeg,png,webp',
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

        return redirect()->route('admin.organizations.show', $org)
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
            'slug'               => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/', Rule::notIn(['admin', 'login', 'logout', 'account', 'build', 'storage']), Rule::unique('organizations', 'slug')->ignore($organization->id)],
            'accent_color'       => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active'          => 'nullable|boolean',
            'logo'               => 'nullable|image|max:2048|mimes:jpg,jpeg,png,webp',
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

        return redirect()->route('admin.organizations.show', $organization)
            ->with('success', "Organization \"{$organization->name}\" updated successfully.");
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        $this->requireSuperAdmin();

        $name = $organization->name;
        $organization->delete();

        return redirect()->route('admin.organizations.index')
            ->with('success', "\"{$name}\" has been archived. Recorded hours and organization data are preserved.");
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
