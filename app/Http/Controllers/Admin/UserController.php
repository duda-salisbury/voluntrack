<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use App\Models\AuditEvent;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $orgId = $request->user()->adminOrganizationId();
        $users = User::with(['organizations' => fn ($q) => $q->when($orgId, fn ($q) => $q->where('organizations.id', $orgId))])
            ->when($request->user()->isPlatformWorkspace(), fn ($q) => $q->addSelect(['last_login_at' => AuditEvent::select('created_at')
                ->whereColumn('actor_id', 'users.id')->where('event_type', 'auth.login')->where('created_at', '>=', now()->subDays(365))
                ->latest('created_at')->limit(1)]))
            ->when($orgId, fn ($q) => $q->whereHas('organizations', fn ($q) => $q->where('organizations.id', $orgId)))
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%')->orWhere('email', 'like', '%'.$request->input('search').'%')))
            ->orderBy('name')->paginate(30)->withQueryString();
        $organizations = $request->user()->isPlatformWorkspace() ? Organization::where('is_active', true)->orderBy('name')->get() : collect();

        return view('admin.users.index', compact('users', 'organizations', 'orgId'));
    }

    public function create(): View
    {
        $organizations = auth()->user()->isPlatformWorkspace() ? Organization::where('is_active', true)->orderBy('name')->get() : collect();
        return view('admin.users.create', compact('organizations'));
    }

    public function store(Request $request): RedirectResponse
    {
        $orgId = $request->user()->adminOrganizationId();
        $data = $request->validate([
            'organization_id' => $orgId === null ? ['required', Rule::exists('organizations', 'id')->where('is_active', true)->whereNull('deleted_at')] : ['nullable'],
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);
        $orgId ??= (int) $data['organization_id'];
        $user = User::withTrashed()->where('email', $data['email'])->first();
        if ($user?->trashed()) {
            return back()->withErrors(['email' => 'This account is unavailable. Contact a platform administrator.'])->withInput();
        }
        if ($user?->hasOrganizationRole($orgId)) {
            return back()->withErrors(['email' => 'This account is already a member.'])->withInput();
        }
        if (! $user) {
            $request->validate(['name' => 'required|string|max:255']);
            DB::transaction(function () use ($data, $orgId) {
                $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Str::random(64)]);
                $user->organizations()->attach($orgId, ['role' => 'volunteer']);
                Audit::record('membership.added', 'membership', 'Added a volunteer membership',
                    actorId: auth()->id(), organizationId: $orgId, subjectUserId: $user->id);
                $status = Password::sendResetLink(['email' => $user->email]);
                if ($status !== Password::RESET_LINK_SENT) {
                    throw ValidationException::withMessages(['email' => __($status)]);
                }
            });
        } else {
            $user->organizations()->attach($orgId, ['role' => 'volunteer']);
            Audit::record('membership.added', 'membership', 'Added a volunteer membership',
                actorId: auth()->id(), organizationId: $orgId, subjectUserId: $user->id);
        }

        return redirect()->route('admin.users.index')->with('success', 'Volunteer membership added.');
    }

    public function edit(User $user): RedirectResponse
    {
        abort_unless($user->id === auth()->id(), 403, 'Personal details can only be changed by the account owner.');
        return redirect()->route('account.profile');
    }

    public function update(Request $request, User $user): never
    {
        abort(403, 'Personal details can only be changed by the account owner.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $orgId = $actor->adminOrganizationId();
        if ($orgId === null) {
            $data = $request->validate(['organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')->whereNull('deleted_at')]]);
            $orgId = (int) $data['organization_id'];
        }
        abort_unless($user->hasOrganizationRole($orgId), 404);
        abort_if($user->id === $actor->id, 403, 'You cannot remove your own administrative membership.');
        abort_if(! $actor->isSuperAdmin() && $user->hasOrganizationRole($orgId, 'admin'), 403, 'Only platform administrators can remove another administrator.');
        $user->organizations()->detach($orgId);
        Audit::record('membership.removed', 'membership', 'Removed an organization membership',
            actorId: $actor->id, organizationId: $orgId, subjectUserId: $user->id);

        return redirect()->route('admin.users.index')->with('success', 'Membership removed. The account and its recorded hours are unchanged.');
    }
}
