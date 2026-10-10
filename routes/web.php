<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SubscriptionRequiredController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(\App\Http\Middleware\RecordPublicVisit::class)->group(function () {
    Route::get('/', [\App\Http\Controllers\StorefrontController::class, 'directory'])->name('home');
    Route::get('/salons', [\App\Http\Controllers\StorefrontController::class, 'directory'])->name('salons');
    Route::get('/trending', [\App\Http\Controllers\StorefrontController::class, 'directory'])->name('trending');
    Route::get('/popular', [\App\Http\Controllers\StorefrontController::class, 'directory'])->name('popular');
    Route::get('/salon/{id}', [\App\Http\Controllers\StorefrontController::class, 'salon'])->whereNumber('id')->name('salon.show');
    Route::get('/services', [\App\Http\Controllers\StorefrontController::class, 'content'])->name('services');
    Route::get('/deals', [\App\Http\Controllers\StorefrontController::class, 'content'])->name('deals');
    Route::get('/about', [\App\Http\Controllers\StorefrontController::class, 'about'])->name('about');
    Route::view('/terms-and-condition', 'storefront.terms', ['title' => 'Terms and Conditions'])->name('terms');
    Route::view('/privacy-policy', 'storefront.privacy', ['title' => 'Privacy Policy'])->name('privacy');
});
Route::get('/sitemap.xml', [\App\Http\Controllers\StorefrontController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [\App\Http\Controllers\StorefrontController::class, 'robots']);
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->hasRole('admin')) {
        return redirect()->route('filament.admin.pages.dashboard');
    }
    if ($user->hasRole('owner') && $user->is_approved) {
        return redirect()->route('filament.admin.pages.owner-dashboard');
    }
    if ($user->hasRole('customer') && ! $user->hasRole('owner')) {
        return app(\App\Http\Controllers\CustomerDashboardController::class)->overview(request(), app(\App\Services\CustomerDashboard::class));
    }

    return view('dashboard');
})->middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth', 'verified'])->name('dashboard');

Route::middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth', 'role:customer'])->prefix('dashboard')->group(function () {
    Route::redirect('/profile', '/profile')->name('customer.profile');
    Route::redirect('/change-password', '/settings/password')->name('customer.password');
    Route::middleware('verified')->controller(\App\Http\Controllers\CustomerDashboardController::class)->group(function () {
        Route::get('/upcoming', 'upcoming')->name('customer.upcoming');
        Route::get('/history', 'history')->name('customer.history');
        Route::get('/favorites', 'favorites')->name('customer.favorites');
        Route::post('/bookings/{id}/cancel', 'cancel')->whereNumber('id')->middleware('throttle:20,1')->name('customer.cancel');
        Route::delete('/favorites/{id}', 'removeFavorite')->whereNumber('id')->middleware('throttle:20,1')->name('customer.favorite.remove');
        Route::get('/notifications', 'notifications')->name('customer.notifications');
        Route::post('/notifications/read-all', 'readAllNotifications')->middleware('throttle:20,1')->name('customer.notifications.read-all');
        Route::post('/notifications/{id}/read', 'readNotification')->whereUuid('id')->middleware('throttle:20,1')->name('customer.notifications.read');
        Route::delete('/notifications', 'deleteAllNotifications')->middleware('throttle:20,1')->name('customer.notifications.clear');
        Route::delete('/notifications/{id}', 'deleteNotification')->whereUuid('id')->middleware('throttle:20,1')->name('customer.notifications.delete');
        Route::redirect('/bookings', '/dashboard/upcoming'); // Previously issued notification links.
    });
});
Route::view('/profile', 'profile')->middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth'])->name('profile');
Route::middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth'])->group(function () {
    \Livewire\Volt\Volt::route('/settings/profile', 'settings.profile')->name('profile.edit');
    \Livewire\Volt\Volt::route('/settings/password', 'settings.password')->name('password.edit');
    \Livewire\Volt\Volt::route('/settings/appearance', 'settings.appearance')->name('appearance.edit');
});

// Keep previously issued query-string links usable during migration.
Route::get('/password/reset', function (Request $request) {
    $data = $request->validate(['token' => 'required|string|max:255', 'email' => 'nullable|email|max:255']);

    return redirect()->route('password.reset', $data);
});
Route::get('/reset-password', function (Request $request) {
    $data = $request->validate(['token' => 'required|string|max:255', 'email' => 'nullable|email|max:255']);

    return redirect()->route('password.reset', $data);
});

// routes/web.php
Route::middleware(['auth'])->group(function () {
    Route::get('/subscription/packages', [App\Http\Controllers\SubscriptionController::class, 'packages'])
        ->name('subscription.packages');
    Route::get('/subscription/checkout/{package}', [App\Http\Controllers\SubscriptionController::class, 'checkout'])
        ->name('subscription.checkout');
    Route::post('/subscription/subscribe/{package}', [App\Http\Controllers\SubscriptionController::class, 'subscribe'])
        ->name('subscription.subscribe');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/subscription/required', [SubscriptionRequiredController::class, 'index'])
        ->name('subscription.required');
});

Route::middleware(['auth', 'verified', 'role:owner', 'approved', 'subscription'])->group(function () {
    // Salon owner routes
    Route::prefix('owner')->group(function () {
        Route::resource('salons', SalonController::class)->only(['store', 'update', 'destroy']);
        Route::resource('salons.staff', StaffController::class);
        Route::resource('salons.services', ServiceController::class);
        Route::resource('bookings', BookingController::class)->only(['store', 'update', 'destroy']);
    });
});

// Retained dashboard bookmarks now enter native pages. Keep legacy write routes during staged migration.
Route::middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth', 'verified', 'role:owner', 'approved'])->group(function () {
    foreach (['dashboard' => 'owner.dashboard', 'salons' => 'salons.index', 'bookings' => 'bookings.index'] as $path => $name) {
        Route::get('/owner/'.$path, \App\Http\Controllers\LegacyDashboardController::class)->defaults('path', $path)->name($name);
    }
});
Route::get('/admin/{path?}', \App\Http\Controllers\LegacyDashboardController::class)
    ->where('path', '(bookings|salons|users|subscriptions|profile|packages|categories|departments)(/create|/[0-9]+(/edit)?)?')
    ->middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth', 'verified', 'role:admin'])->name('legacy.admin');
Route::get('/owner/{path?}', \App\Http\Controllers\LegacyDashboardController::class)
    ->where('path', '(dashboard|salons|bookings|services|subscription|profile)(/create|/[0-9]+(/edit)?)?')
    ->middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth', 'verified', 'role:owner', 'approved'])->name('legacy.owner');

require __DIR__.'/auth.php';
