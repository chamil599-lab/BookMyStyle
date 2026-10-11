<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PublicContentController;
use App\Http\Controllers\Api\PublicSalonController;
use App\Http\Controllers\Api\SalonRatingController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

// Management has moved to Filament. Retired endpoints never replay mutations.
Route::middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth:sanctum', 'verified', 'role:admin', 'throttle:60,1'])->prefix('admin')->group(function () {
    foreach ([
        ['get', '/dashboard/stats', 'filament.admin.pages.dashboard'],
        ['get', '/dashboard/revenue-chart', 'filament.admin.pages.dashboard'],
        ['get', '/dashboard/visits-chart', 'filament.admin.pages.dashboard'],
        ['get', '/dashboard/subscription-chart', 'filament.admin.pages.dashboard'],
        ['get', '/dashboard/top-packages', 'filament.admin.pages.dashboard'],
        ['get', '/bookings', 'filament.admin.resources.bookings.index'],
        ['patch', '/bookings/{id}/status', 'filament.admin.resources.bookings.index'],
        ['get', '/salons', 'filament.admin.resources.salons.index'],
        ['get', '/users', 'filament.admin.resources.users.index'],
        ['patch', '/users/{id}/approve', 'filament.admin.resources.users.index'],
        ['delete', '/users/{id}', 'filament.admin.resources.users.index'],
        ['get', '/subscriptions', 'filament.admin.resources.user-subscriptions.index'],
        ['patch', '/subscriptions/{id}/status', 'filament.admin.resources.user-subscriptions.index'],
        ['get', '/packages', 'filament.admin.resources.subscription-packages.index'],
        ['post', '/packages', 'filament.admin.resources.subscription-packages.index'],
        ['put', '/packages/{id}', 'filament.admin.resources.subscription-packages.index'],
        ['delete', '/packages/{id}', 'filament.admin.resources.subscription-packages.index'],
        ['get', '/categories', 'filament.admin.resources.categories.index'],
        ['post', '/categories', 'filament.admin.resources.categories.index'],
        ['put', '/categories/{id}', 'filament.admin.resources.categories.index'],
        ['delete', '/categories/{id}', 'filament.admin.resources.categories.index'],
        ['get', '/departments', 'filament.admin.resources.departments.index'],
        ['post', '/departments', 'filament.admin.resources.departments.index'],
        ['put', '/departments/{id}', 'filament.admin.resources.departments.index'],
        ['delete', '/departments/{id}', 'filament.admin.resources.departments.index'],
    ] as [$method, $path, $destination]) {
        Route::$method($path, \App\Http\Controllers\RetiredManagementController::class)
            ->defaults('native_route', $destination)->whereNumber(['id', 'package']);
    }
});

Route::middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth:sanctum', 'verified', 'role:owner', 'approved', 'throttle:60,1'])->prefix('owner')->group(function () {
    foreach ([
        ['get', '/dashboard/stats', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/revenue-chart', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/top-services', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/bookings-by-status', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/salons', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/calendar', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/analytics', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/daily-revenue', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/monthly-revenue', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/profit-breakdown', 'filament.admin.pages.owner-dashboard'],
        ['get', '/dashboard/profit-by-month', 'filament.admin.pages.owner-dashboard'],
        ['get', '/salons', 'filament.admin.resources.salons.index'],
        ['post', '/salons', 'filament.admin.resources.salons.index'],
        ['get', '/salons/{id}', 'filament.admin.resources.salons.index'],
        ['put', '/salons/{id}', 'filament.admin.resources.salons.index'],
        ['delete', '/salons/{id}', 'filament.admin.resources.salons.index'],
        ['get', '/bookings', 'filament.admin.resources.bookings.index'],
        ['post', '/bookings', 'filament.admin.resources.bookings.index'],
        ['put', '/bookings/{id}', 'filament.admin.resources.bookings.index'],
        ['delete', '/bookings/{id}', 'filament.admin.resources.bookings.index'],
        ['patch', '/bookings/{id}/status', 'filament.admin.resources.bookings.index'],
        ['get', '/services', 'filament.admin.resources.services.index'],
        ['post', '/services', 'filament.admin.resources.services.index'],
        ['put', '/services/{id}', 'filament.admin.resources.services.index'],
        ['delete', '/services/{id}', 'filament.admin.resources.services.index'],
        ['get', '/departments', 'filament.admin.resources.departments.index'],
        ['get', '/categories', 'filament.admin.resources.categories.index'],
        ['get', '/limits', 'filament.admin.pages.owner-dashboard'],
        ['get', '/subscription/packages', 'filament.admin.resources.user-subscriptions.index'],
        ['post', '/subscription/subscribe/{package}', 'filament.admin.resources.user-subscriptions.index'],
        ['get', '/subscription/history', 'filament.admin.resources.user-subscriptions.index'],
    ] as [$method, $path, $destination]) {
        Route::$method($path, \App\Http\Controllers\RetiredManagementController::class)
            ->defaults('native_route', $destination)->whereNumber(['id', 'package']);
    }
});

Route::middleware([\App\Http\Middleware\PrivateAccountResponse::class, 'auth:sanctum'])->group(function () {
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus'])
        ->middleware(['verified', 'role:admin|owner', 'approved', 'subscription.active', 'throttle:20,1']);
    Route::get('/salons/{salonId}/bookings', [BookingController::class, 'getSalonBookings'])
        ->middleware(['verified', 'role:admin|owner', 'approved', 'subscription.active', 'throttle:20,1']);
    Route::post('/bookings/my-bookings', [BookingController::class, 'getUserBookings']);
    Route::get('/salons/{salon}/can-rate', [SalonRatingController::class, 'canRate']);
    Route::post('/salons/{salon}/ratings', [SalonRatingController::class, 'store'])->middleware('throttle:reviews');

    Route::get('/notifications', [NotificationController::class, 'index'])->middleware(['verified', 'throttle:10,1']);
    Route::get('/notifications/stream', [NotificationController::class, 'stream'])->middleware(['verified', 'throttle:10,1']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->middleware(['verified', 'throttle:40,1']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->middleware(['verified', 'throttle:40,1']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->middleware(['verified', 'throttle:40,1']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll'])->middleware(['verified', 'throttle:40,1']);

    Route::prefix('customer')->group(function () {
        Route::get('/stats', [CustomerController::class, 'stats']);
        Route::get('/bookings/upcoming', [CustomerController::class, 'upcomingBookings']);
        Route::get('/bookings/history', [CustomerController::class, 'bookingHistory']);
        Route::patch('/bookings/{id}/cancel', [CustomerController::class, 'cancelBooking'])->whereNumber('id')->middleware(['verified', 'throttle:20,1']);
        Route::get('/profile', [CustomerController::class, 'profile']);
        Route::put('/profile', [CustomerController::class, 'updateProfile']);

        Route::get('/favorites', [CustomerController::class, 'getFavorites']);
        Route::post('/favorites/{salonId}/toggle', [CustomerController::class, 'toggleFavorite'])->whereNumber('salonId')->middleware(['verified', 'throttle:20,1']);
        Route::get('/favorites/{salonId}/check', [CustomerController::class, 'checkFavorite'])->whereNumber('salonId');
    });

    Route::post('/image-cycles', [PublicContentController::class, 'storeImageCycle'])->middleware(['verified', 'role:admin', 'throttle:20,1']);
});

Route::get('/salons', [PublicSalonController::class, 'index']);
Route::get('/salons/simple', [PublicSalonController::class, 'simpleList']);
Route::get('/salons/{salon}', [PublicSalonController::class, 'show']);
Route::get('/salons/{salon}/services', [PublicSalonController::class, 'services']);
Route::get('/salons/{salon}/working-hours', [PublicSalonController::class, 'workingHours']);
Route::get('/salons/{salon}/booked-times', [PublicSalonController::class, 'bookedTimes']);
Route::get('/bookings/{salon}/{date}', [PublicSalonController::class, 'bookedTimesLegacy']);

Route::post('/bookings', [BookingApiController::class, 'store'])->middleware('throttle:public-writes');
Route::get('/bookings/count', [BookingApiController::class, 'count']);

Route::get('/salons/{salon}/ratings', [SalonRatingController::class, 'index']);

Route::get('/departments', [PublicContentController::class, 'departments']);
Route::get('/stats', [PublicContentController::class, 'stats']);
Route::post('/site-visits', [PublicContentController::class, 'recordVisit'])->middleware('throttle:public-writes');
Route::get('/service-pages', [PublicContentController::class, 'servicePages']);
Route::get('/dealspages', [PublicContentController::class, 'dealsPages']);
Route::get('/image-cycles', [PublicContentController::class, 'imageCycles']);
Route::get('/team-members', [PublicContentController::class, 'teamMembers']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth-public');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth-login');
    Route::post('/google', [AuthController::class, 'googleAuth'])->middleware('throttle:auth-public');
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:auth-public');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:auth-public');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});
