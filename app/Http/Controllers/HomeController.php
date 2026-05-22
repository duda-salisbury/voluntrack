<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->get('q', ''));

        $organizations = Organization::where('is_active', true)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->get();

        return view('home', compact('organizations', 'search'));
    }
}
