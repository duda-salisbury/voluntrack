<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Organization;
use App\Models\OrganizationRequest;
use App\Models\SpecialEvent;
use App\Models\User;
use App\Models\VolunteerEntry;
use App\Support\Audit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function overview(Request $request): View
    {
        $organization = auth()->user()->adminOrganization();
        $recentQuery = VolunteerEntry::query()
            ->when($organization, fn ($q) => $q->where('organization_id', $organization->id))
            ->where('created_at', '>=', now()->subDays(30));
        $recentEntries = (clone $recentQuery)->count();
        $recentHours = (float) (clone $recentQuery)->sum('hours');
        $recent = (clone $recentQuery)->with(['location', 'specialEvent', 'organization'])->latest()->limit(5)->get();

        if ($organization) {
            $locationCount = $organization->locations()->where('is_active', true)->count();

            return view('admin.overview', compact('organization', 'locationCount', 'recentEntries', 'recentHours', 'recent'));
        }

        $withoutAdmins = Organization::whereDoesntHave('users', fn ($q) => $q->where('organization_user.role', 'admin'));
        $platformStats = [
            'organizations' => Organization::count(),
            'active' => Organization::where('is_active', true)->count(),
            'without_admins' => (clone $withoutAdmins)->count(),
        ];
        $attention = (clone $withoutAdmins)->orderBy('name')->limit(5)->get();
        $pendingRequests = OrganizationRequest::where('status', 'pending')->count();

        return view('admin.overview', compact('organization', 'platformStats', 'attention', 'pendingRequests', 'recentEntries', 'recentHours', 'recent'));
    }

    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);
        $entries = (clone $query)->with(['location', 'specialEvent', 'matchedByAdmin', 'createdByAdmin', 'organization'])
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

    public function volunteerActivity(Request $request): View
    {
        $query = $this->filteredQuery($request);
        $accounts = (clone $query)->where('is_group_total', false)->whereNotNull('volunteer_user_id')
            ->selectRaw('volunteer_user_id, SUM(hours) as total, COUNT(*) as submissions, MAX(volunteered_at) as last_worked')
            ->groupBy('volunteer_user_id')
            ->with('volunteerUser')
            ->orderByDesc('total')
            ->paginate(20)->withQueryString();
        $unlinked = (clone $query)->where('is_group_total', false)->whereNull('volunteer_user_id')->count();

        return view('admin.volunteer-activity', compact('accounts', 'unlinked') + $this->filterOptions($request));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->filteredQuery($request)->with(['organization', 'location', 'specialEvent', 'createdByAdmin']);
        $actorId = $request->user()->id;
        $orgId = $request->user()->adminOrganizationId();
        if ($orgId === null && $request->filled('organization_id')) {
            $orgId = Organization::findOrFail($request->input('organization_id'))->id;
        }

        return response()->streamDownload(function () use ($query, $actorId, $orgId) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Organization', 'Volunteer', 'Work date', 'Location', 'Special event', 'Hours', 'Matching', 'Source', 'Notes', 'Contribution type', 'Reported participants', 'Entered by'], ',', '"', '');
            foreach ($query->lazyById(200) as $entry) {
                $row = [
                    $entry->organization?->name ?? 'Unavailable',
                    $entry->is_group_total ? 'Group total' : ($entry->name ?? 'Anonymous'),
                    $entry->volunteered_at->format('Y-m-d'),
                    $entry->location?->name ?? 'Unavailable',
                    $entry->specialEvent?->name ?? '',
                    number_format((float) $entry->hours, 2, '.', ''),
                    $entry->is_matched ? 'Yes' : 'No',
                    $entry->source ?? '',
                    $entry->notes ?? '',
                    $entry->is_group_total ? 'Group total' : 'Individual',
                    $entry->is_group_total && $entry->participant_count !== null ? (string) $entry->participant_count : '',
                    $entry->is_group_total ? ($entry->createdByAdmin?->name ?? 'Unavailable') : '',
                ];
                $row = array_map(fn ($value) => preg_match('/^(?:[\\t\\r\\n]|[\\s\\x00-\\x1f]*[=+\\-@])/u', $value) ? "'".$value : $value, $row);
                fputcsv($output, $row, ',', '"', '');
            }
            fclose($output);
            Audit::record('report.exported', 'report', 'Exported a volunteer hours report',
                actorId: $actorId, organizationId: $orgId);
        }, 'voluntrack-contributions-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filteredQuery(Request $request): Builder
    {
        $orgId = auth()->user()->adminOrganizationId();
        $request->validate([
            'organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')->whereNull('deleted_at')],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')->when($orgId !== null, fn ($rule) => $rule->where('organization_id', $orgId))],
            'special_event_id' => ['nullable', 'integer', Rule::exists('special_events', 'id')->when($orgId !== null, fn ($rule) => $rule->where('organization_id', $orgId))],
            'volunteer_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')->whereNull('deleted_at')->when($orgId !== null, fn ($rule) => $rule->where(fn ($query) => $query
                ->whereExists(fn ($q) => $q->selectRaw('1')->from('organization_user')->whereColumn('organization_user.user_id', 'users.id')->where('organization_user.organization_id', $orgId))
                ->orWhereExists(fn ($q) => $q->selectRaw('1')->from('volunteer_entries')->whereColumn('volunteer_entries.volunteer_user_id', 'users.id')->where('volunteer_entries.organization_id', $orgId)->whereNull('volunteer_entries.deleted_at'))))],
            'volunteer' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:50',
            'matched' => ['nullable', Rule::in(['yes', 'no'])],
            'event_status' => ['nullable', Rule::in(['event', 'ordinary'])],
            'date_preset' => ['nullable', Rule::in(['last30', 'this_month', 'last_month', 'this_year', 'custom'])],
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
        if ($request->filled('volunteer_user_id') || $request->filled('volunteer')) {
            $query->where('is_group_total', false);
        }
        if ($request->filled('volunteer')) {
            $query->where('name', 'like', '%'.$request->input('volunteer').'%');
        }
        [$dateFrom, $dateTo] = match ($request->input('date_preset')) {
            'last30' => [today()->subDays(29)->toDateString(), today()->toDateString()],
            'this_month' => [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()],
            'last_month' => [today()->subMonthNoOverflow()->startOfMonth()->toDateString(), today()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'this_year' => [today()->startOfYear()->toDateString(), today()->endOfYear()->toDateString()],
            default => [$request->input('date_from'), $request->input('date_to')],
        };
        if ($dateFrom) {
            $query->whereDate('volunteered_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('volunteered_at', '<=', $dateTo);
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
        $identities = (clone $query)->where('is_group_total', false)->select(['organization_id', 'volunteer_user_id', 'anon_token', 'name'])
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
            'individual_hours' => (float) (clone $query)->where('is_group_total', false)->sum('hours'),
            'group_hours' => (float) (clone $query)->where('is_group_total', true)->sum('hours'),
            'reported_participations' => (int) (clone $query)->where('is_group_total', true)->sum('participant_count'),
            'matched_hours' => (float) (clone $query)->where('is_matched', true)->sum('hours'),
            'unmatched_hours' => (float) (clone $query)->where('is_matched', false)->sum('hours'),
            'estimated_volunteers' => $knownIdentities->count(),
            'unidentified_entries' => (clone $query)->where('is_group_total', false)->whereNull('volunteer_user_id')->whereNull('anon_token')
                ->where(fn ($q) => $q->whereNull('name')->orWhere('name', ''))->count(),
            'event_hours' => (float) (clone $query)->whereNotNull('special_event_id')->sum('hours'),
        ];
    }

    private function filterOptions(Request $request): array
    {
        $orgId = auth()->user()->adminOrganizationId() ?? ($request->filled('organization_id') ? (int) $request->input('organization_id') : null);

        return [
            'locations' => Location::withTrashed()->when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))->orderBy('name')->get(),
            'events' => SpecialEvent::when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))->orderBy('name')->get(),
            'volunteers' => User::when($orgId !== null, fn ($q) => $q->where(fn ($q) => $q
                ->whereHas('organizations', fn ($q) => $q->where('organizations.id', $orgId))
                ->orWhereHas('volunteerEntries', fn ($q) => $q->where('organization_id', $orgId))))->orderBy('name')->get(['id', 'name', 'email']),
            'sources' => VolunteerEntry::when($orgId !== null, fn ($q) => $q->where('organization_id', $orgId))->whereNotNull('source')->distinct()->orderBy('source')->pluck('source'),
            'organizations' => auth()->user()->isPlatformWorkspace() ? Organization::orderBy('name')->get() : collect(),
        ];
    }
}
