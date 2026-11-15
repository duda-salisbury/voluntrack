<?php

namespace App\Providers;

use App\Models\Location;
use App\Models\Organization;
use App\Models\SpecialEvent;
use App\Models\VolunteerEntry;
use App\Observers\AuditObserver;
use App\Support\Audit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();
        foreach ([Organization::class, Location::class, SpecialEvent::class, VolunteerEntry::class] as $model) {
            $model::observe(AuditObserver::class);
        }
        Event::listen(Login::class, function (Login $event) {
            $ip = request()->ip();
            Audit::record('auth.login', 'authentication', 'Signed in successfully', actorId: $event->user->getAuthIdentifier(),
                ipAddress: filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null);
        });
        Event::listen(Failed::class, function (Failed $event) {
            $ip = request()->ip();
            Audit::record('auth.failed', 'authentication', 'Failed sign-in attempt',
                subjectUserId: $event->user?->getAuthIdentifier(), ipAddress: filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null);
        });
        Event::listen(Logout::class, fn (Logout $event) => $event->user
            ? Audit::record('auth.logout', 'authentication', 'Signed out', actorId: $event->user->getAuthIdentifier()) : null);
        Event::listen(Registered::class, fn (Registered $event) => Audit::record(
            'account.registered', 'account', 'Registered an account', actorId: $event->user->getAuthIdentifier(),
            subjectUserId: $event->user->getAuthIdentifier()
        ));
        Event::listen(PasswordReset::class, fn (PasswordReset $event) => Audit::record(
            'account.password_reset', 'account', 'Completed a password reset', actorId: $event->user->getAuthIdentifier(),
            subjectUserId: $event->user->getAuthIdentifier()
        ));
        ResetPassword::createUrlUsing(fn ($user, $token) => route('account.password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));
    }
}
