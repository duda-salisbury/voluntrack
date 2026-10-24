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
        if (!auth()->check() || !auth()->user()->is_admin) {
            return redirect()->route('login')
                ->with('error', 'You must be an administrator to access this area.');
        }

        if (auth()->user()->organization_id !== null) {
            abort_unless(auth()->user()->organization?->is_active, 403, 'Your organization is inactive. Contact the platform administrator.');
        }

        return $next($request);
    }
}
