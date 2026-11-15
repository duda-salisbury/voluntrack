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

        abort_unless($user->isPlatformWorkspace() || $user->adminOrganization(), 403, 'Your administrative access is unavailable for this organization.');
        view()->share('org', $user->adminOrganization());

        return $next($request);
    }
}
