<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EntryController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\VolunteerHourController;
use Illuminate\Support\Facades\Route;

// Public volunteer hour logging
Route::get('/', [VolunteerHourController::class, 'index'])->name('hours.index');
Route::post('/hours', [VolunteerHourController::class, 'store'])->name('hours.store');
Route::get('/success', [VolunteerHourController::class, 'success'])->name('hours.success');

// Admin Authentication
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin Routes (Protected)
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('entries', EntryController::class);
    Route::post('entries/{entry}/toggle-match', [EntryController::class, 'toggleMatch'])->name('entries.toggle-match');
    Route::post('entries/{entry}/update-notes', [EntryController::class, 'updateNotes'])->name('entries.update-notes');
});
