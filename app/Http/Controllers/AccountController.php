<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function profile(Request $request): View
    {
        return view('account.profile', ['user' => $request->user(), 'memberships' => $request->user()->organizations()->orderBy('name')->get()]);
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
        return back()->with('success', 'Your profile has been updated.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate(['current_password' => 'required|current_password', 'password' => 'required|string|min:8|confirmed']);
        $request->user()->update(['password' => $data['password']]);
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
            $request->session()->put('admin_organization_id', $organization->id);
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

    public function platform(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);
        $data = $request->validate(['destination' => ['nullable', Rule::in(['overview', 'organizations'])]]);
        $request->session()->forget('admin_organization_id');
        return redirect()->route(($data['destination'] ?? 'overview') === 'organizations' ? 'admin.organizations.index' : 'admin.overview');
    }

    public function join(Request $request, Organization $organization): RedirectResponse
    {
        abort_unless($organization->is_active, 404);
        DB::table('organization_user')->insertOrIgnore([
            'organization_id' => $organization->id, 'user_id' => $request->user()->id,
            'role' => 'volunteer', 'created_at' => now(), 'updated_at' => now(),
        ]);
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
