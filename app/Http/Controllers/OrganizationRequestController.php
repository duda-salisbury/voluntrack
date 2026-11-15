<?php

namespace App\Http\Controllers;

use App\Models\OrganizationRequest;
use App\Models\User;
use App\Notifications\OrganizationRequestSubmitted;
use App\Support\Audit;
use App\Support\OrganizationRequestMatches;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class OrganizationRequestController extends Controller
{
    public function create(Request $request): View
    {
        return view('organization-requests.create', ['draft' => $request->session()->get('organization_request_draft', [])]);
    }

    public function authenticate(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('organization-requests.create');
        }
        $request->session()->put('url.intended', route('organization-requests.create'));

        return view('organization-requests.authenticate');
    }

    public function index(Request $request): View
    {
        $requests = OrganizationRequest::where('requester_id', $request->user()->id)
            ->with('organization')->latest()->paginate(15);

        return view('organization-requests.index', compact('requests'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['new', 'existing'])],
            'organization_name' => 'required|string|max:160',
            'website' => 'nullable|url:http,https|max:2048',
            'contact_name' => 'required|string|max:255',
            'contact_email' => 'required|email|max:255',
            'description' => 'nullable|string|max:1000',
        ]);
        if (! $request->user()) {
            $request->session()->put('organization_request_draft', $data);
            return redirect()->route('organization-requests.authenticate');
        }

        $nameHash = OrganizationRequestMatches::nameHash($data['organization_name']);
        $domain = OrganizationRequestMatches::domain($data['website'] ?? null);
        if ($domain && strlen($domain) > 253) {
            throw ValidationException::withMessages(['website' => 'The website domain is too long.']);
        }

        $existing = null;
        if ($data['type'] === 'existing') {
            $matches = OrganizationRequestMatches::organizations($nameHash, null)
                ->filter(fn ($org) => ! $org->trashed() && $org->is_active);
            if ($matches->count() !== 1) {
                throw ValidationException::withMessages(['organization_name' => 'Enter the exact name of an active organization. If you cannot identify it uniquely, contact a platform administrator.']);
            }
            $existing = $matches->first();
            if ($request->user()->hasOrganizationRole($existing->id, 'admin')) {
                throw ValidationException::withMessages(['organization_name' => 'You already administer this organization.']);
            }
            $data['organization_name'] = $existing->name;
        }

        $userId = $request->user()->id;
        $keys = [
            'pending_name_key' => hash('sha256', $userId.':name:'.$nameHash),
            'pending_domain_key' => $domain ? hash('sha256', $userId.':domain:'.$domain) : null,
            'pending_org_key' => $existing ? hash('sha256', $userId.':org:'.$existing->id) : null,
        ];
        $duplicate = fn () => OrganizationRequest::where('requester_id', $userId)->where('status', 'pending')
            ->where(function ($query) use ($keys) {
                $query->where('pending_name_key', $keys['pending_name_key']);
                if ($keys['pending_domain_key']) {
                    $query->orWhere('pending_domain_key', $keys['pending_domain_key']);
                }
                if ($keys['pending_org_key']) {
                    $query->orWhere('pending_org_key', $keys['pending_org_key']);
                }
            })->exists();
        if ($duplicate()) {
            throw ValidationException::withMessages(['organization_name' => 'You already have a pending request for this organization.']);
        }

        try {
            $organizationRequest = OrganizationRequest::create([
                ...$data, ...$keys,
                'name_hash' => $nameHash,
                'website_domain' => $domain,
                'requester_id' => $userId,
                'existing_organization_id' => $existing?->id,
                'status' => 'pending',
            ]);
        } catch (QueryException $exception) {
            if ($duplicate()) {
                throw ValidationException::withMessages(['organization_name' => 'You already have a pending request for this organization.']);
            }
            throw $exception;
        }
        $request->session()->forget('organization_request_draft');
        Audit::record('organization_request.submitted', 'organization', 'Submitted an organization request',
            actorId: $userId, subjectUserId: $userId, subjectType: 'organization_request', subjectId: $organizationRequest->id);

        $recipients = User::where('is_platform_admin', true)->get();
        $failed = $recipients->isEmpty();
        if ($failed) {
            Log::warning('Organization request submitted, but no platform administrators are available.');
        }
        foreach ($recipients as $recipient) {
            try {
                $recipient->notify(new OrganizationRequestSubmitted($organizationRequest));
            } catch (Throwable $exception) {
                $failed = true;
                Log::error('Organization request notification failed.', ['exception_type' => $exception::class]);
            }
        }

        return redirect()->route('organization-requests.index')->with(
            $failed ? 'error' : 'success',
            $failed ? 'Your request was saved, but an administrator notification could not be sent. Please contact the platform administrator.' : 'Your request has been submitted for review.'
        );
    }
}
