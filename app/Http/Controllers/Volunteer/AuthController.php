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
use Illuminate\View\View;

/**
 * Handles registration, login, and logout for volunteer accounts.
 *
 * This is intentionally kept separate from the admin LoginController so
 * the two surfaces never bleed into each other.
 *
 * Volunteers use the same 'web' guard and users table as admins.
 * The distinction is simply is_admin = false.
 */
class AuthController extends Controller
{
    // -------------------------------------------------------------------------
    // Register
    // -------------------------------------------------------------------------

    public function showRegister(Organization $org): View|RedirectResponse
    {
        abort_if(! $org->is_active, 404);

        if ($this->volunteerIsLoggedIn()) {
            return redirect()->route('org.hours.index', $org);
        }

        return view('hours.account.register', compact('org'));
    }

    public function register(Organization $org, Request $request): RedirectResponse
    {
        abort_if(! $org->is_active, 404);

        if ($this->volunteerIsLoggedIn()) {
            return redirect()->route('org.hours.index', $org);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'],
            'password'        => Hash::make($validated['password']),
            'is_admin'        => false,
            'organization_id' => $org->id,
        ]);

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
        if ($this->volunteerIsLoggedIn()) {
            return redirect()->route('org.hours.index', $org);
        }

        return view('hours.account.login', compact('org'));
    }

    public function login(Organization $org, Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = auth()->user();

            // Block admin accounts from using the volunteer login form.
            // Admins use /login instead.
            if ($user->is_admin) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Please use the admin login page.',
                ])->onlyInput('email');
            }

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
        // Only log out non-admin users from this route.
        // Admin logout continues to use /logout (the existing route).
        if (auth()->check() && ! auth()->user()->is_admin) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return redirect()->route('org.hours.index', $org);
    }

    // -------------------------------------------------------------------------

    private function volunteerIsLoggedIn(): bool
    {
        return auth()->check() && ! auth()->user()->is_admin;
    }
}
