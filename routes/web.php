<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SubscriptionRequiredController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

$frontendUrl = static function (): string {
    if (config('app.env') === 'local') {
        return rtrim((string) env('FRONTEND_URL', 'http://localhost:5173'), '/');
    }

    return rtrim((string) env('FRONTEND_URL', 'https://bookmystyle.limach.lk'), '/');
};

$redirectToFrontend = static function (Request $request, string $path = '') use ($frontendUrl) {
    $target = $frontendUrl().($path !== '' ? "/{$path}" : '');
    $query = $request->getQueryString();

    if (! empty($query)) {
        $target .= "?{$query}";
    }

    return redirect()->away($target);
};

Route::get('/', fn (Request $request) => $redirectToFrontend($request))->name('home');
Route::get('/login', fn (Request $request) => $redirectToFrontend($request, 'login'))->name('login');
Route::get('/register', fn (Request $request) => $redirectToFrontend($request, 'register'))->name('register');
Route::get('/forgot-password', fn (Request $request) => $redirectToFrontend($request, 'forgot-password'))->name('password.request');
Route::get('/reset-password', fn (Request $request) => $redirectToFrontend($request, 'reset-password'));
Route::get('/deals', fn (Request $request) => $redirectToFrontend($request, 'deals'))->name('deals');
Route::get('/about', fn (Request $request) => $redirectToFrontend($request, 'about'));
Route::get('/services', fn (Request $request) => $redirectToFrontend($request, 'services'));
Route::get('/salons', fn (Request $request) => $redirectToFrontend($request, 'salons'));
Route::get('/salon/{id}', fn (Request $request, string $id) => $redirectToFrontend($request, "salon/{$id}"));
Route::get('/trending', fn (Request $request) => $redirectToFrontend($request, 'trending'));
Route::get('/popular', fn (Request $request) => $redirectToFrontend($request, 'popular'));
Route::get('/terms-and-condition', fn (Request $request) => $redirectToFrontend($request, 'terms-and-condition'));
Route::get('/privacy-policy', fn (Request $request) => $redirectToFrontend($request, 'privacy-policy'));
Route::get('/dashboard/{any?}', fn (Request $request, ?string $any = null) => $redirectToFrontend($request, $any ? "dashboard/{$any}" : 'dashboard'))
    ->where('any', '.*');

Route::get('/password/reset', function () {
    $token = request('token');
    $email = request('email');
    
    // Frontend URL based on environment
    $frontendUrl = config('app.env') === 'local' 
        ? 'http://localhost:5173' 
        : 'https://bookmystyle.limach.lk';
    
    return redirect("{$frontendUrl}/reset-password?token={$token}&email={$email}");
})->name('password.reset');

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

Route::middleware(['auth', 'subscription'])->group(function () {
    // Salon owner routes
    Route::prefix('owner')->group(function () {
        Route::get('/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');
        Route::resource('salons', SalonController::class);
        Route::resource('salons.staff', StaffController::class);
        Route::resource('salons.services', ServiceController::class);
        Route::resource('bookings', BookingController::class);
    });
});
