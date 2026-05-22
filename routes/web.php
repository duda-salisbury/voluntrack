<?php

use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EntryController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Volunteer\AuthController as VolunteerAuthController;
use App\Http\Controllers\Volunteer\ForgotPasswordController;
use App\Http\Controllers\Volunteer\HistoryController as VolunteerHistoryController;
use App\Http\Controllers\Volunteer\ResetPasswordController;
use App\Http\Controllers\VolunteerHourController;
use App\Models\Organization;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Root: redirect to the first active organization's public form
// ---------------------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

// ---------------------------------------------------------------------------
// Admin authentication (defined before the {org:slug} catch-all to prevent
// 'login' / 'logout' from being resolved as organization slugs)
// ---------------------------------------------------------------------------
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// ---------------------------------------------------------------------------
// Admin (protected)
// ---------------------------------------------------------------------------
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');

    // Entries
    Route::resource('entries', EntryController::class);
    Route::post('entries/{entry}/toggle-match',  [EntryController::class, 'toggleMatch'])->name('entries.toggle-match');
    Route::post('entries/{entry}/update-notes',  [EntryController::class, 'updateNotes'])->name('entries.update-notes');
    Route::patch('entries/{entry}/inline',        [EntryController::class, 'updateInline'])->name('entries.update-inline');

    // Branding (org-admin: edit their own org's settings)
    Route::get('branding',        [BrandingController::class, 'edit'])->name('branding.edit');
    Route::put('branding',        [BrandingController::class, 'update'])->name('branding.update');

    // Locations
    Route::resource('locations', LocationController::class)->except(['show']);
    Route::patch('locations/{location}/toggle-active', [LocationController::class, 'toggleActive'])
        ->name('locations.toggle-active');

    // Organizations (super-admin only)
    Route::resource('organizations', OrganizationController::class)->except(['show']);
    Route::patch('organizations/{organization}/toggle-active', [OrganizationController::class, 'toggleActive'])
        ->name('organizations.toggle-active');

    // Users
    Route::resource('users', UserController::class)->except(['show']);
});

// ---------------------------------------------------------------------------
// Global volunteer password reset (org-agnostic — email links must resolve
// independently of any org slug)
// ---------------------------------------------------------------------------
Route::prefix('account/password')->name('account.password.')->group(function () {
    Route::get('/forgot',        [ForgotPasswordController::class, 'showLinkRequestForm'])->name('request');
    Route::post('/forgot',       [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('email');
    Route::get('/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('reset');
    Route::post('/reset',        [ResetPasswordController::class, 'reset'])->name('update');
});

// ---------------------------------------------------------------------------
// Public volunteer form — org-scoped via slug
// Must be defined LAST so the {org:slug} wildcard doesn't swallow admin/auth routes
// ---------------------------------------------------------------------------
Route::prefix('{org:slug}')->name('org.')->group(function () {
    Route::get('/',        [VolunteerHourController::class, 'index'])->name('hours.index');
    Route::post('/hours',  [VolunteerHourController::class, 'store'])->name('hours.store');
    Route::get('/success', [VolunteerHourController::class, 'success'])->name('hours.success');

    // Volunteer account (auth + history — within org context)
    Route::get('/account/register',  [VolunteerAuthController::class, 'showRegister'])->name('account.register');
    Route::post('/account/register', [VolunteerAuthController::class, 'register'])->name('account.register.post');
    Route::get('/account/login',     [VolunteerAuthController::class, 'showLogin'])->name('account.login');
    Route::post('/account/login',    [VolunteerAuthController::class, 'login'])->name('account.login.post');
    Route::post('/account/logout',   [VolunteerAuthController::class, 'logout'])->name('account.logout');
    Route::get('/account/history',   [VolunteerHistoryController::class, 'index'])->name('account.history');
});

