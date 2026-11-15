<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\AuditEvent;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function profile(Request $request): View
    {
        $lastLogin = AuditEvent::where('actor_id', $request->user()->id)->where('event_type', 'auth.login')
            ->where('created_at', '>=', now()->subDays(365))->latest('created_at')->value('created_at');

        return view('account.profile', ['user' => $request->user(), 'memberships' => $request->user()->organizations()->orderBy('name')->get(), 'lastLogin' => $lastLogin]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($request->user()->id)],
            'current_password' => 'required|current_password',
        ]);
        $user = $request->user();
        $user->name = $data['name'];
        if ($user->email !== $data['email']) {
            $user->email = $data['email'];
            $user->email_verified_at = null;
        }
        $user->save();
        if ($user->wasChanged(['name', 'email'])) {
            Audit::record('account.profile_updated', 'account', 'Updated account profile', actorId: $user->id, subjectUserId: $user->id);
        }
        return back()->with('success', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => 'required|current_password', 'password' => 'required|string|min:8|confirmed']);
        $request->user()->update(['password' => $data['password']]);
        Audit::record('account.password_changed', 'account', 'Changed account password', actorId: $request->user()->id, subjectUserId: $request->user()->id);
        return back()->with('success', 'Your password has been updated.');
    }

    public function organizations(Request $request): View
    {
        $memberships = $request->user()->organizations()->orderBy('name')->get();
        return view('account.organizations', compact('memberships'));
    }

    public function switchOrganization(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->is_active && $request->user()->hasOrganizationRole($organization->id), 403);
        $data = $request->validate(['destination' => ['nullable', Rule::in(['overview', 'settings', 'people'])]]);
        if ($request->user()->hasOrganizationRole($organization->id, 'admin')) {
            $this->selectAdminOrganization($request, $organization);
            $route = match ($data['destination'] ?? 'overview') {
                'settings' => 'admin.branding.edit',
                'people' => 'admin.users.index',
                default => 'admin.overview',
            };
            return redirect()->route($route);
        }
        abort_if(isset($data['destination']) && $data['destination'] !== 'overview', 403);
        $request->session()->forget('admin_organization_id');
        return redirect()->route('org.account.history', $organization);
    }

    public function openWorkspace(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $this->selectAdminOrganization($request, $organization);

        return redirect()->route('admin.overview');
    }

    private function selectAdminOrganization(Request $request, Organization $organization): void
    {
        $request->session()->put('admin_organization_id', $organization->id);
        if ($request->user()->isSuperAdmin()) {
            Audit::record('workspace.entered', 'organization', 'Platform administrator opened the organization workspace',
                actorId: $request->user()->id, organizationId: $organization->id, subjectType: 'organization', subjectId: $organization->id);
        }
    }

    public function platform(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['destination' => ['nullable', Rule::in(['overview', 'organizations'])]]);
        $request->session()->forget('admin_organization_id');
        if (($data['destination'] ?? 'overview') === 'organizations') {
            return redirect()->route('admin.organizations.index', $request->session()->get('platform_organization_filters', []));
        }

        return redirect()->route('admin.overview');
    }

    public function join(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->is_active, 404);
        $added = DB::table('organization_user')->insertOrIgnore([
            'organization_id' => $organization->id, 'user_id' => $request->user()->id,
            'role' => 'volunteer', 'created_at' => now(), 'updated_at' => now(),
        ]);
        if ($added) {
            Audit::record('membership.added', 'membership', 'Joined an organization as a volunteer', actorId: $request->user()->id,
                organizationId: $organization->id, subjectUserId: $request->user()->id);
        }
        return redirect()->route('org.hours.index', $organization)->with('success', 'Organization added to your account.');
    }

    public function hours(Request $request): View
    {
        $request->validate(['organization_id' => 'nullable|integer']);
        $user = $request->user();
        $totalHours = $user->volunteerEntries()->sum('hours');
        $entries = $user->volunteerEntries()->with(['organization', 'location', 'specialEvent'])
            ->when($request->filled('organization_id'), fn ($q) => $q->where('organization_id', $request->input('organization_id')))
            ->orderByDesc('volunteered_at')->latest()->paginate(20)->withQueryString();
        $memberships = Organization::withTrashed()
            ->whereHas('volunteerEntries', fn ($query) => $query->where('volunteer_user_id', $user->id))
            ->orWhereHas('users', fn ($query) => $query->where('users.id', $user->id))
            ->orderBy('name')->get();
        return view('account.hours', compact('entries', 'totalHours', 'memberships'));
    }
}
