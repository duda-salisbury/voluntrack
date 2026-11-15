<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditEvent;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $platform = $user->isPlatformWorkspace();
        $organization = $platform ? null : $user->adminOrganization();
        abort_unless($platform || $organization, 403);

        $categories = [
            'membership' => 'Memberships',
            'organization' => 'Organization',
            'hours' => 'Volunteer hours',
            'location' => 'Locations',
            'event' => 'Special events',
            'report' => 'Reports',
        ];
        if ($platform) {
            $categories = ['authentication' => 'Sign-ins', 'account' => 'Accounts'] + $categories;
        }

        $filters = $request->validate([
            'category' => ['nullable', Rule::in(array_keys($categories))],
            'organization_id' => 'nullable|integer|min:1',
            'user' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
            'date_from' => 'nullable|date',
            'date_to' => ['nullable', 'date', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
        ]);
        $base = AuditEvent::query()
            ->when(! $platform, fn ($query) => $query->where('organization_id', $organization->id)->whereIn('category', array_keys($categories)));
        $recent = (clone $base)->where('created_at', '>=', now()->subDays(30));
        $stats = $platform
            ? [
                'Successful sign-ins' => (clone $recent)->where('event_type', 'auth.login')->count(),
                'Failed sign-ins' => (clone $recent)->where('event_type', 'auth.failed')->count(),
                'Administrative activity' => (clone $recent)->where(fn ($query) => $query
                    ->whereIn('category', ['organization', 'membership', 'location', 'event', 'report'])
                    ->orWhereIn('event_type', ['entry.admin_created', 'entry.updated', 'entry.deleted']))->count(),
            ]
            : [
                'Hours activity' => (clone $recent)->where('category', 'hours')->count(),
                'Membership activity' => (clone $recent)->where('category', 'membership')->count(),
                'Other changes' => (clone $recent)->whereIn('category', ['organization', 'location', 'event', 'report'])->count(),
            ];

        $events = $base->with(['actor', 'subjectUser', 'organization'])
            ->when($platform && ! empty($filters['organization_id']), fn ($query) => $query->where('organization_id', $filters['organization_id']))
            ->when(! empty($filters['category']), fn ($query) => $query->where('category', $filters['category']))
            ->when(! empty($filters['search']), fn ($query) => $query->where('description', 'like', '%'.trim($filters['search']).'%'))
            ->when(! empty($filters['user']), fn ($query) => $query->where(function ($query) use ($filters) {
                $name = '%'.trim($filters['user']).'%';
                $query->whereHas('actor', fn ($query) => $query->where('name', 'like', $name))
                    ->orWhereHas('subjectUser', fn ($query) => $query->where('name', 'like', $name));
            }))
            ->when(! empty($filters['date_from']), fn ($query) => $query->where('created_at', '>=', \Carbon\Carbon::parse($filters['date_from'])->startOfDay()))
            ->when(! empty($filters['date_to']), fn ($query) => $query->where('created_at', '<', \Carbon\Carbon::parse($filters['date_to'])->addDay()->startOfDay()))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(25)->withQueryString();
        $organizations = $platform ? Organization::withTrashed()->orderBy('name')->get(['id', 'name', 'deleted_at']) : collect();

        return view('admin.activity.index', compact('events', 'stats', 'categories', 'organizations', 'platform', 'organization'));
    }
}
