<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\Category;
use App\Models\DealsPage;
use App\Models\Department;
use App\Models\FeaturedSalon;
use App\Models\ImageCycle;
use App\Models\Salon;
use App\Models\Service;
use App\Models\ServicePage;
use App\Models\SubscriptionPackage;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\UserSubscription;
use App\Observers\BookingObserver;
use App\Policies\AdminOnlyPolicy;
use App\Policies\BookingPolicy;
use App\Policies\OwnedCataloguePolicy;
use App\Policies\SubscriptionPackagePolicy;
use App\Policies\UserManagementPolicy;
use App\Policies\UserSubscriptionPolicy;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

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
        foreach ([Salon::class, Service::class] as $model) {
            Gate::policy($model, OwnedCataloguePolicy::class);
        }
        Gate::policy(UserSubscription::class, UserSubscriptionPolicy::class);
        Gate::policy(User::class, UserManagementPolicy::class);
        Gate::policy(SubscriptionPackage::class, SubscriptionPackagePolicy::class);
        foreach ([Category::class,
            Department::class, FeaturedSalon::class, ImageCycle::class,
            ServicePage::class, DealsPage::class, TeamMember::class] as $model) {
            Gate::policy($model, AdminOnlyPolicy::class);
        }
        Gate::define('view-salon-bookings', [BookingPolicy::class, 'viewSalonBookings']);
        Livewire::addPersistentMiddleware([
            RedirectIfAuthenticated::class,
            EnsureEmailIsVerified::class,
            AuthenticateSession::class,
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
