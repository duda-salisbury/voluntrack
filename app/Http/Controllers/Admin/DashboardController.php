<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Models\User;
use App\Models\VolunteerEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function overview(Request $request): View
    {
        $query = $this->filteredQuery($request);
        $stats = $this->buildStats($query);
        $recent = (clone $query)->with(['location', 'specialEvent', 'organization'])->latest()->limit(5)->get();
        $organization = auth()->user()->organization;
        $locationCount = Location::when($organization, fn ($q) => $q->where('organization_id', $organization->id))->count();
        $organizations = auth()->user()->isSuperAdmin()
            ? Organization::withCount('users')->orderBy('name')->get()
            : collect();

        return view('admin.overview', compact('stats', 'recent', 'organization', 'locationCount', 'organizations'));
    }

    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);
        $entries = (clone $query)->with(['location', 'specialEvent', 'matchedByAdmin', 'organization'])
            ->orderByDesc('volunteered_at')->latest()->paginate(20)->withQueryString();
        $stats = $this->buildStats($query);

        return view('admin.dashboard', compact('entries', 'stats') + $this->filterOptions($request));
    }

    public function stats(Request $request): View
    {
        $stats = $this->buildStats($this->filteredQuery($request));

        return view('admin.partials.stats-cards', compact('stats'));
    }

    public function reports(Request $request): View
    {
        $query = $this->filteredQuery($request);
        $stats = $this->buildStats($query);
        $byLocation = (clone $query)->selectRaw('location_id, SUM(hours) as total, COUNT(*) as submissions')
            ->groupBy('location_id')->with('location')->orderByDesc('total')->get();
        $byEvent = (clone $query)->selectRaw('special_event_id, SUM(hours) as total, COUNT(*) as submissions')
            ->groupBy('special_event_id')->with('specialEvent')->orderByDesc('total')->get();
        $byMonth = (clone $query)->selectRaw('volunteered_at, SUM(hours) as total')
            ->groupBy('volunteered_at')->orderBy('volunteered_at')->get()
            ->groupBy(fn ($row) => $row->volunteered_at->format('Y-m'))
            ->map(fn ($rows) => (float) $rows->sum('total'));
        $recent = (clone $query)->with(['location', 'specialEvent', 'organization'])->latest()->limit(10)->get();

        return view('admin.reports', compact('stats', 'byLocation', 'byEvent', 'byMonth', 'recent') + $this->filterOptions($request));
    }

    private function filteredQuery(Request $request): Builder
    {
        $orgId = auth()->user()->organization_id;
        $request->validate([
            'organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')->whereNull('deleted_at')],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->when($orgId !== null, fn ($rule) => $rule->where('organization_id', $orgId))],
            'special_event_id' => ['nullable', 'integer', Rule::exists('special_events', 'id')->when($orgId !== null, fn ($rule) => $rule->where('organization_id', $orgId))],
            'volunteer_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->when($orgId !== null, fn ($rule) => $rule->where('organization_id', $orgId))],
            'volunteer' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:50',
            'matched' => ['nullable', Rule::in(['yes', 'no'])],
            'event_status' => ['nullable', Rule::in(['event', 'ordinary'])],
            'date_from' => 'nullable|date',
            'date_to' => ['nullable', 'date', ...($request->filled('date_from') ? ['after_or_equal:date_from'] : [])],
        ]);
        $query = VolunteerEntry::query()
            ->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId));
        if ($orgId === null && $request->filled('organization_id')) {
            $query->where('organization_id', $request->input('organization_id'));
        }
        foreach (['location_id', 'special_event_id', 'volunteer_user_id', 'source'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }
        if ($request->filled('volunteer')) {
            $query->where('name', 'like', '%'.$request->input('volunteer').'%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('volunteered_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('volunteered_at', '<=', $request->input('date_to'));
        }
        if ($request->filled('matched')) {
            $query->where('is_matched', $request->input('matched') === 'yes');
        }
        if ($request->input('event_status') === 'event') {
            $query->whereNotNull('special_event_id');
        } elseif ($request->input('event_status') === 'ordinary') {
            $query->whereNull('special_event_id');
        }

        return $query;
    }

    private function buildStats(Builder $query): array
    {
        $identities = (clone $query)->select(['organization_id', 'volunteer_user_id', 'anon_token', 'name'])
            ->distinct()->get();
        $knownIdentities = $identities->map(function ($entry) {
            $identity = match (true) {
                $entry->volunteer_user_id !== null => 'account:'.$entry->volunteer_user_id,
                filled($entry->anon_token) => 'device:'.$entry->anon_token,
                filled($entry->name) => 'name:'.mb_strtolower(trim($entry->name)),
                default => null,
            };

            return $identity === null ? null : $entry->organization_id.':'.$identity;
        })->filter()->unique();

        return [
            'total_entries' => (clone $query)->count(),
            'total_hours' => (float) (clone $query)->sum('hours'),
            'matched_hours' => (float) (clone $query)->where('is_matched', true)->sum('hours'),
            'unmatched_hours' => (float) (clone $query)->where('is_matched', false)->sum('hours'),
            'estimated_volunteers' => $knownIdentities->count(),
            'unidentified_entries' => (clone $query)->whereNull('volunteer_user_id')->whereNull('anon_token')
                ->where(fn ($q) => $q->whereNull('name')->orWhere('name', ''))->count(),
            'event_hours' => (float) (clone $query)->whereNotNull('special_event_id')->sum('hours'),
        ];
    }

    private function filterOptions(Request $request): array
    {
        $orgId = auth()->user()->organization_id ?? ($request->filled('organization_id') ? (int) $request->input('organization_id') : null);

        return [
            'locations' => Location::withTrashed()->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))->orderBy('name')->get(),
            'events' => SpecialEvent::when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))->orderBy('name')->get(),
            'volunteers' => User::where('is_admin', false)->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))->orderBy('name')->get(['id', 'name', 'email']),
            'sources' => VolunteerEntry::when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))->whereNotNull('source')->distinct()->orderBy('source')->pluck('source'),
            'organizations' => auth()->user()->isSuperAdmin() ? Organization::orderBy('name')->get() : collect(),
        ];
    }
}
