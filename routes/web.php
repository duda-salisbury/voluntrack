<?php

use App\Http\Controllers\Admin\BrandingController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\OrganizationRequestController;
use App\Http\Controllers\OrganizationRequestRegistrationController;
use App\Http\Controllers\Admin\OrganizationRequestController as AdminOrganizationRequestController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EntryController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SpecialEventController;
use App\Http\Controllers\Volunteer\AuthController as VolunteerAuthController;
use App\Http\Controllers\Volunteer\ForgotPasswordController;
use App\Http\Controllers\Volunteer\HistoryController as VolunteerHistoryController;
use App\Http\Controllers\Volunteer\ResetPasswordController;
use App\Http\Controllers\VolunteerHourController;
use App\Models\Organization;
use Illuminate\Support\Facades\Route;

// ---------------------------------------------------------------------------
// Root: role-aware landing page and on-demand organization search
// ---------------------------------------------------------------------------
Route::get('/', [HomeController::class, 'index'])->name('home');

// ---------------------------------------------------------------------------
// Admin authentication (defined before the {org:slug} catch-all to prevent
// 'login' / 'logout' from being resolved as organization slugs)
// ---------------------------------------------------------------------------
Route::get('/login',  [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/my-account/organization-requests/new', [OrganizationRequestController::class, 'create'])->name('organization-requests.create');
Route::get('/my-account/organization-requests/continue', [OrganizationRequestController::class, 'authenticate'])->name('organization-requests.authenticate');
Route::get('/my-account/organization-requests/register', [OrganizationRequestRegistrationController::class, 'create'])->name('organization-requests.register');
Route::post('/my-account/organization-requests/register', [OrganizationRequestRegistrationController::class, 'store'])->middleware('throttle:6,1')->name('organization-requests.register.store');
Route::post('/my-account/organization-requests', [OrganizationRequestController::class, 'store'])->middleware('throttle:6,1')->name('organization-requests.store');
Route::get('/my-account/organization-requests', [OrganizationRequestController::class, 'index'])->middleware('auth')->name('organization-requests.index');

Route::middleware('auth')->prefix('my-account')->name('account.')->group(function () {
    Route::get('/profile', [AccountController::class, 'profile'])->name('profile');
    Route::put('/profile', [AccountController::class, 'updateProfile'])->name('profile.update');
    Route::put('/password', [AccountController::class, 'updatePassword'])->name('profile.password');
    Route::get('/organizations', [AccountController::class, 'organizations'])->name('organizations');
    Route::post('/organizations/{organization}/switch', [AccountController::class, 'switchOrganization'])->name('organizations.switch');
    Route::post('/organizations/{organization}/join', [AccountController::class, 'join'])->name('organizations.join');
    Route::post('/platform', [AccountController::class, 'platform'])->name('platform');
    Route::get('/hours', [AccountController::class, 'hours'])->name('hours');
});

// ---------------------------------------------------------------------------
// Admin (protected)
// ---------------------------------------------------------------------------
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/overview', [DashboardController::class, 'overview'])->name('overview');
    Route::get('/stats', [DashboardController::class, 'stats'])->name('stats');
    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
    Route::get('/reports/export', [DashboardController::class, 'export'])->name('reports.export');
    Route::get('/volunteer-activity', [DashboardController::class, 'volunteerActivity'])->name('volunteer-activity');
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::get('/organization-requests', [AdminOrganizationRequestController::class, 'index'])->name('organization-requests.index');
    Route::get('/organization-requests/{organizationRequest}', [AdminOrganizationRequestController::class, 'show'])->name('organization-requests.show');
    Route::post('/organization-requests/{organizationRequest}/decision', [AdminOrganizationRequestController::class, 'decide'])->middleware('throttle:30,1')->name('organization-requests.decide');
    Route::post('/organization-requests/{organizationRequest}/resend', [AdminOrganizationRequestController::class, 'resend'])->middleware('throttle:6,1')->name('organization-requests.resend');

    // Entries
    Route::get('entries/group', [EntryController::class, 'createGroup'])->name('entries.group');
    Route::post('entries/group', [EntryController::class, 'storeGroup'])->name('entries.group.store');
    Route::get('entries/group-total', [EntryController::class, 'createGroupTotal'])->name('entries.group-total');
    Route::post('entries/group-total', [EntryController::class, 'storeGroupTotal'])->name('entries.group-total.store');
    Route::resource('entries', EntryController::class)->except(['index', 'show']);
    Route::post('entries/{entry}/toggle-match',  [EntryController::class, 'toggleMatch'])->name('entries.toggle-match');
    Route::post('entries/{entry}/update-notes',  [EntryController::class, 'updateNotes'])->name('entries.update-notes');
    Route::patch('entries/{entry}/inline',        [EntryController::class, 'updateInline'])->name('entries.update-inline');

    // Branding (org-admin: edit their own org's settings)
    Route::get('branding',        [BrandingController::class, 'edit'])->name('branding.edit');
    Route::put('branding',        [BrandingController::class, 'update'])->name('branding.update');

    // Locations
    Route::resource('locations', LocationController::class)->except(['show']);
    Route::resource('special-events', SpecialEventController::class)->except(['destroy']);
    Route::patch('locations/{location}/toggle-active', [LocationController::class, 'toggleActive'])
        ->name('locations.toggle-active');

    // Organizations (super-admin only)
    Route::resource('organizations', OrganizationController::class);
    Route::post('organizations/{organization}/administrators', [OrganizationController::class, 'inviteAdministrator'])
        ->middleware('throttle:6,1')->name('organizations.administrators');
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
    Route::post('/forgot',       [ForgotPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:6,1')->name('email');
    Route::get('/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('reset');
    Route::post('/reset',        [ResetPasswordController::class, 'reset'])->middleware('throttle:6,1')->name('update');
});

// ---------------------------------------------------------------------------
// Public volunteer form — org-scoped via slug
// Must be defined LAST so the {org:slug} wildcard doesn't swallow admin/auth routes
// ---------------------------------------------------------------------------
Route::prefix('{org:slug}')->name('org.')->group(function () {
    Route::get('/', [VolunteerHourController::class, 'index'])->name('hours.legacy');
    Route::get('/log-hours', [VolunteerHourController::class, 'index'])->name('hours.index');
    Route::get('/manifest.webmanifest', [VolunteerHourController::class, 'manifest'])->name('manifest');
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
