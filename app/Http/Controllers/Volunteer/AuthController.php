<?php

namespace App\Http\Controllers\Volunteer;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Handles organization-facing registration, login, and logout.
 */
class AuthController extends Controller
{
    // -------------------------------------------------------------------------
    // Register
    // -------------------------------------------------------------------------

    public function showRegister(Organization $org): View|RedirectResponse
    {
        abort_if(! $org->is_active, 404);

        if (auth()->check()) {
            return redirect()->route('org.hours.index', $org);
        }

        return view('hours.account.register', compact('org'));
    }

    public function register(Organization $org, Request $request): RedirectResponse
    {
        abort_if(! $org->is_active, 404);

        if (auth()->check()) {
            return redirect()->route('org.hours.index', $org);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = DB::transaction(function () use ($validated, $org) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $user->organizations()->attach($org->id, ['role' => 'volunteer']);
            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('org.hours.index', $org)
            ->with('success', "Welcome, {$user->name}! Your account has been created.");
    }

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    public function showLogin(Organization $org): View|RedirectResponse
    {
        abort_if(! $org->is_active, 404);
        if (auth()->check()) {
            return redirect()->route('org.hours.index', $org);
        }

        return view('hours.account.login', compact('org'));
    }

    public function login(Organization $org, Request $request): RedirectResponse
    {
        abort_if(! $org->is_active, 404);
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();
            $request->session()->forget('admin_organization_id');

            $user = auth()->user();

            return redirect()->route('org.hours.index', $org)
                ->with('success', "Welcome back, {$user->name}!");
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function logout(Organization $org, Request $request): RedirectResponse
    {
        if (auth()->check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('org.hours.index', $org);
    }

}
