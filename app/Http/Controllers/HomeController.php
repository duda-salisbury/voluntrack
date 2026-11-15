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
        if ($user?->isSuperAdmin()) {
            return redirect()->route('admin.overview');
        }
        if ($user?->organization?->is_active) {
            return redirect()->route($user->is_admin ? 'admin.overview' : 'org.account.history', $user->is_admin ? [] : ['org' => $user->organization]);
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
