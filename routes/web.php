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
Route::get('/deals', fn (Request $request) => $redirectToFrontend($request, 'deals'))->name('deals');
Route::get('/about', fn (Request $request) => $redirectToFrontend($request, 'about'));
Route::get('/services', fn (Request $request) => $redirectToFrontend($request, 'services'));
Route::get('/salons', fn (Request $request) => $redirectToFrontend($request, 'salons'));
Route::get('/salon/{id}', fn (Request $request, string $id) => $redirectToFrontend($request, "salon/{$id}"));
Route::get('/trending', fn (Request $request) => $redirectToFrontend($request, 'trending'));
Route::get('/popular', fn (Request $request) => $redirectToFrontend($request, 'popular'));
Route::get('/terms-and-condition', fn (Request $request) => $redirectToFrontend($request, 'terms-and-condition'));
Route::get('/privacy-policy', fn (Request $request) => $redirectToFrontend($request, 'privacy-policy'));
Route::get('/dashboard', function () {
    $user = auth()->user();
    if ($user->hasRole('admin')) {
        return redirect()->route('filament.admin.pages.dashboard');
    }
    if ($user->hasRole('owner') && $user->is_approved) {
        return redirect()->route('filament.admin.pages.owner-dashboard');
    }

    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/dashboard/{any}', fn (Request $request, string $any) => $redirectToFrontend($request, 'dashboard/'.$any))->where('any', '.*');
Route::view('/profile', 'profile')->middleware('auth')->name('profile');
Route::middleware('auth')->group(function () {
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
        Route::get('/dashboard', [OwnerController::class, 'dashboard'])->name('owner.dashboard');
        Route::resource('salons', SalonController::class);
        Route::resource('salons.staff', StaffController::class);
        Route::resource('salons.services', ServiceController::class);
        Route::resource('bookings', BookingController::class);
    });
});

require __DIR__.'/auth.php';
