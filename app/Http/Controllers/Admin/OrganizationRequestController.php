<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationRequest;
use App\Models\User;
use App\Notifications\OrganizationRequestDecision;
use App\Support\Audit;
use App\Support\OrganizationRequestMatches;
use App\Support\OrganizationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class OrganizationRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeReviewer();
        $data = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'approved', 'declined'])],
            'search' => 'nullable|string|max:255',
        ]);
        $requests = OrganizationRequest::with(['requester', 'organization'])
            ->when(! empty($data['status']), fn ($query) => $query->where('status', $data['status']))
            ->when(! empty($data['search']), fn ($query) => $query->where(function ($q) use ($data) {
                $q->where('organization_name', 'like', '%'.trim($data['search']).'%')
                    ->orWhere('contact_name', 'like', '%'.trim($data['search']).'%')
                    ->orWhere('contact_email', 'like', '%'.trim($data['search']).'%');
            }))
            ->orderByRaw("case when status = 'pending' then 0 else 1 end")
            ->latest()->paginate(20)->withQueryString();
        $pendingCount = OrganizationRequest::where('status', 'pending')->count();

        return view('admin.organization-requests.index', compact('requests', 'pendingCount'));
    }

    public function show(OrganizationRequest $organizationRequest): View
    {
        $this->authorizeReviewer();
        $organizationRequest->load(['requester', 'reviewer', 'existingOrganization', 'organization']);
        $matches = OrganizationRequestMatches::organizations($organizationRequest->name_hash, $organizationRequest->website_domain);
        $pendingMatches = OrganizationRequestMatches::pending($organizationRequest);

        return view('admin.organization-requests.show', compact('organizationRequest', 'matches', 'pendingMatches'));
    }

    public function decide(Request $request, OrganizationRequest $organizationRequest): RedirectResponse
    {
        $this->authorizeReviewer();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'declined'])],
            'review_notes' => 'nullable|string|max:2000',
            'decline_reason' => 'nullable|string|max:1000',
            'verified' => 'sometimes|accepted',
        ]);
        $decision = DB::transaction(function () use ($request, $organizationRequest, $data) {
            $lock = DB::table('organization_request_locks')->where('id', 1)->lockForUpdate()->first();
            if (! $lock) {
                throw new RuntimeException('Organization request review lock is missing.');
            }
            $item = OrganizationRequest::whereKey($organizationRequest->id)->lockForUpdate()->firstOrFail();
            if ($item->status !== 'pending') {
                throw ValidationException::withMessages(['decision' => 'This request has already been reviewed.']);
            }
            $recipient = User::whereKey($item->requester_id)->lockForUpdate()->first();
            if ($data['decision'] === 'approved' && ! $recipient) {
                throw ValidationException::withMessages(['decision' => 'The requester account is unavailable.']);
            }

            $organization = null;
            if ($data['decision'] === 'approved') {
                if ($item->type === 'new') {
                    if (OrganizationRequestMatches::organizations($item->name_hash, $item->website_domain)->isNotEmpty()) {
                        throw ValidationException::withMessages(['decision' => 'A potentially matching organization already exists. Review it and decline this request if the applicant should request access instead.']);
                    }
                    $base = substr(Str::slug($item->organization_name), 0, 80);
                    $base = rtrim($base, '-') ?: 'organization';
                    if (in_array($base, ['admin', 'login', 'logout', 'account', 'my-account', 'build', 'storage'], true)) {
                        $base .= '-organization';
                    }
                    $slug = $base;
                    for ($suffix = 2; Organization::withTrashed()->where('slug', $slug)->exists(); $suffix++) {
                        $slug = $base.'-'.$suffix;
                    }
                    $organization = Organization::create([
                        'name' => $item->organization_name,
                        'slug' => $slug,
                        'accent_color' => '#0d6efd',
                        'is_active' => true,
                        'settings' => OrganizationSettings::defaults()->toArray(),
                    ]);
                } else {
                    if (empty($data['verified'])) {
                        throw ValidationException::withMessages(['verified' => 'Verify that the requester is authorized to administer this organization before granting access.']);
                    }
                    $organization = Organization::whereKey($item->existing_organization_id)->where('is_active', true)->lockForUpdate()->first();
                    if (! $organization) {
                        throw ValidationException::withMessages(['decision' => 'The requested organization is no longer available.']);
                    }
                    if ($recipient->hasOrganizationRole($organization->id, 'admin')) {
                        throw ValidationException::withMessages(['decision' => 'The requester already administers this organization.']);
                    }
                }

                $previousRole = $recipient->organizations()->where('organizations.id', $organization->id)->first()?->pivot->role;
                if ($previousRole) {
                    $recipient->organizations()->updateExistingPivot($organization->id, ['role' => 'admin']);
                } else {
                    $recipient->organizations()->attach($organization->id, ['role' => 'admin']);
                }
                Audit::record($previousRole ? 'membership.role_changed' : 'membership.added', 'membership',
                    $previousRole ? 'Changed membership role to Administrator' : 'Added an administrator membership',
                    actorId: $request->user()->id, organizationId: $organization->id, subjectUserId: $recipient->id);
            }
            $item->update([
                'status' => $data['decision'],
                'organization_id' => $organization?->id,
                'reviewed_by' => $request->user()->id,
                'decided_at' => now(),
                'review_notes' => $data['review_notes'] ?? null,
                'decline_reason' => $data['decision'] === 'declined' ? ($data['decline_reason'] ?? null) : null,
                'pending_name_key' => null,
                'pending_domain_key' => null,
                'pending_org_key' => null,
            ]);
            Audit::record('organization_request.'.$item->status, 'organization', 'Organization request '.$item->status,
                actorId: $request->user()->id, subjectUserId: $item->requester_id, subjectType: 'organization_request', subjectId: $item->id);

            return $item;
        });

        return $this->sendDecision($decision);
    }

    public function resend(OrganizationRequest $organizationRequest): RedirectResponse
    {
        $this->authorizeReviewer();
        abort_if($organizationRequest->status === 'pending', 404);

        return $this->sendDecision($organizationRequest);
    }

    private function sendDecision(OrganizationRequest $item): RedirectResponse
    {
        $recipient = User::find($item->requester_id);
        if (! $recipient) {
            return redirect()->route('admin.organization-requests.show', $item)
                ->with('error', 'The decision was saved, but the requester account is unavailable for email.');
        }
        try {
            $recipient->notify(new OrganizationRequestDecision($item));
        } catch (Throwable $exception) {
            Log::error('Organization request decision notification failed.', ['exception_type' => $exception::class]);
            return redirect()->route('admin.organization-requests.show', $item)
                ->with('error', 'The decision was saved, but the email could not be sent. Please retry it below.');
        }

        return redirect()->route('admin.organization-requests.show', $item)->with('success', 'Decision saved and the requester notified.');
    }

    private function authorizeReviewer(): void
    {
        abort_unless(auth()->user()?->isPlatformWorkspace(), 403);
    }
}
