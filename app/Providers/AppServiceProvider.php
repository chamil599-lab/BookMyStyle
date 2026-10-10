<?php

namespace App\Providers;

use App\Models\Booking;
use App\Observers\BookingObserver;
use App\Policies\BookingPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Booking::observe(BookingObserver::class);
        Gate::policy(Booking::class, BookingPolicy::class);
        Gate::define('view-salon-bookings', [BookingPolicy::class, 'viewSalonBookings']);
        \Livewire\Livewire::addPersistentMiddleware([
            \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
        ]);

        RateLimiter::for('auth-login', fn (Request $request) => [
            Limit::perMinute(30)->by('login-ip:'.$request->ip()),
            Limit::perMinute(5)->by('login-identity:'.hash('sha256', strtolower((string) $request->input('email')).'|'.$request->ip())),
        ]);
        RateLimiter::for('auth-public', fn (Request $request) => Limit::perMinute(10)->by('auth:'.$request->ip()));
        RateLimiter::for('public-writes', fn (Request $request) => Limit::perMinute(10)->by('public:'.$request->ip()));
        RateLimiter::for('reviews', fn (Request $request) => Limit::perMinute(5)->by('review:'.$request->user()->id));
    }
}
