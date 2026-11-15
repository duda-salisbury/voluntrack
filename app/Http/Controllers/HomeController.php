<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user?->isSuperAdmin() && ! $request->boolean('find')) {
            $request->session()->forget('admin_organization_id');
            return redirect()->route('admin.overview');
        }
        if ($user && ! $request->boolean('find')) {
            $organization = $user->organizations()->where('organizations.is_active', true)
                ->orderBy('organizations.name')->first();
            if ($organization) {
                if ($organization->pivot->role === 'admin') {
                    $request->session()->put('admin_organization_id', $organization->id);
                    return redirect()->route('admin.overview');
                }
                return redirect()->route('org.account.history', $organization);
            }
        }

        $validated = $request->validate(['q' => 'nullable|string|max:255']);
        $search = trim($validated['q'] ?? '');
        $organizations = mb_strlen($search) >= 2
            ? Organization::where('is_active', true)
                ->where('name', 'like', "%{$search}%")
                ->orderBy('name')->limit(10)->get()
            : collect();

        return view($request->header('HX-Request') ? 'home-results' : 'home', compact('organizations', 'search') + ['org' => null]);
    }
}
