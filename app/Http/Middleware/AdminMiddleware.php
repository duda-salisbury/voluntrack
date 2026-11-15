<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->guest(route('login'))
                ->with('error', 'You must be an administrator to access this area.');
        }

        $user = auth()->user();
        if (! $request->session()->has('admin_organization_id') && ! $user->isSuperAdmin()) {
            $organization = $user->organizations()->wherePivot('role', 'admin')
                ->where('organizations.is_active', true)->orderBy('organizations.name')->first();
            if ($organization) {
                $request->session()->put('admin_organization_id', $organization->id);
            }
        }

        $organization = $user->adminOrganization();
        abort_unless($user->isPlatformWorkspace() || $organization, 403, 'Your administrative access is unavailable for this organization.');

        if ($user->isSuperAdmin()) {
            $markers = array_filter([$request->input('_admin_workspace'), $request->header('X-Admin-Workspace')], fn ($value) => $value !== null);
            abort_if($organization && ! $request->isMethodSafe() && ! $markers, 409,
                'The organization workspace changed. Reload the page before continuing.');
            foreach ($markers as $workspace) {
                abort_unless(is_scalar($workspace) && (string) $workspace === (string) ($organization?->id ?? 0), 409,
                    'The organization workspace changed. Reload the page before continuing.');
            }
        }

        view()->share('org', $organization);

        return $next($request);
    }
}
