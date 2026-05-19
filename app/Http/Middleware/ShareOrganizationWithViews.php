<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Appended to the 'web' middleware group so it runs after SubstituteBindings.
 * At that point the route's 'org' parameter is already a bound Organization
 * model (for public org-slug routes).
 *
 * Shares $org globally so it is available in every Blade view and partial
 * without passing it manually through every controller.
 */
class ShareOrganizationWithViews
{
    public function handle(Request $request, Closure $next): Response
    {
        // Public routes: resolved by route model binding from {org:slug}
        $org = $request->route('org');

        // Admin routes: resolve from the authenticated user
        if (! $org && auth()->check()) {
            $org = auth()->user()->organization;
        }

        // Make $org available in every view rendered during this request
        view()->share('org', $org);

        return $next($request);
    }
}
