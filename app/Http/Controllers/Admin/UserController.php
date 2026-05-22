<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** The org the current admin is scoped to (null = super-admin). */
    private function adminOrgId(): ?int
    {
        return auth()->user()->organization_id;
    }

    /** Ensure the given user belongs to the admin's org (or allow super-admin). */
    private function authorizeUser(User $user): void
    {
        $orgId = $this->adminOrgId();
        if ($orgId !== null && $user->organization_id !== $orgId) {
            abort(403);
        }
    }

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    public function index(Request $request): View
    {
        $adminOrgId = $this->adminOrgId();

        $query = User::with('organization')->orderBy('name');

        // Org-admins see only their own org's users
        if ($adminOrgId !== null) {
            $query->where('organization_id', $adminOrgId);
        }

        // Filters
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name',  'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('is_admin', $request->role === 'admin');
        }

        // Super-admin org filter
        if ($request->filled('org') && $adminOrgId === null) {
            if ($request->org === 'none') {
                $query->whereNull('organization_id');
            } else {
                $query->where('organization_id', $request->org);
            }
        }

        $users = $query->paginate(30)->withQueryString();

        $organizations = $adminOrgId === null
            ? Organization::orderBy('name')->get()
            : collect();

        return view('admin.users.index', compact('users', 'organizations'));
    }

    // -------------------------------------------------------------------------
    // Create / Store
    // -------------------------------------------------------------------------

    public function create(): View
    {
        $adminOrgId = $this->adminOrgId();

        $organizations = $adminOrgId === null
            ? Organization::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('admin.users.create', compact('organizations', 'adminOrgId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $adminOrgId = $this->adminOrgId();

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => 'required|email|max:255|unique:users,email',
            'password'        => 'required|string|min:8|confirmed',
            'is_admin'        => 'boolean',
            'organization_id' => $adminOrgId === null
                ? 'nullable|exists:organizations,id'
                : 'nullable',
        ]);

        // Org-admins always create users in their own org
        $orgId = $adminOrgId ?? ($validated['organization_id'] ?: null);

        // Org-admins can only create volunteer accounts (not other admins)
        $isAdmin = $adminOrgId === null
            ? (bool) ($validated['is_admin'] ?? false)
            : false;

        User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),
            'is_admin'        => $isAdmin,
            'organization_id' => $orgId,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "User \"{$validated['name']}\" created successfully.");
    }

    // -------------------------------------------------------------------------
    // Edit / Update
    // -------------------------------------------------------------------------

    public function edit(User $user): View
    {
        $this->authorizeUser($user);

        $adminOrgId = $this->adminOrgId();

        $organizations = $adminOrgId === null
            ? Organization::where('is_active', true)->orderBy('name')->get()
            : collect();

        return view('admin.users.edit', compact('user', 'organizations', 'adminOrgId'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        $adminOrgId = $this->adminOrgId();

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'email'           => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password'        => 'nullable|string|min:8|confirmed',
            'is_admin'        => 'boolean',
            'organization_id' => $adminOrgId === null
                ? 'nullable|exists:organizations,id'
                : 'nullable',
        ]);

        // Org-admins cannot escalate users to admin
        $isAdmin = $adminOrgId === null
            ? (bool) ($validated['is_admin'] ?? false)
            : $user->is_admin;

        $orgId = $adminOrgId === null
            ? ($validated['organization_id'] ?: null)
            : $user->organization_id;

        $update = [
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'is_admin'        => $isAdmin,
            'organization_id' => $orgId,
        ];

        if (! empty($validated['password'])) {
            $update['password'] = Hash::make($validated['password']);
        }

        $user->update($update);

        return redirect()->route('admin.users.index')
            ->with('success', "User \"{$user->name}\" updated.");
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(User $user): RedirectResponse
    {
        $this->authorizeUser($user);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', "User \"{$name}\" deleted.");
    }
}
