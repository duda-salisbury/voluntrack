<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class OrganizationRequestRegistrationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('organization-requests.create');
        }
        $request->session()->put('url.intended', route('organization-requests.create'));

        return view('organization-requests.register', ['draft' => $request->session()->get('organization_request_draft', [])]);
    }

    public function store(Request $request): RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('organization-requests.create');
        }
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => Hash::make($data['password'])]);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('organization-requests.create')->with('success', 'Account created. Review and submit your request.');
    }
}
