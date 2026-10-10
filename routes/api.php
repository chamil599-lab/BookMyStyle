<?php

use App\Http\Controllers\Api\AdminDashboardController;
use App\Http\Controllers\Api\AdminManagementController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\OwnerDashboardController;
use App\Http\Controllers\Api\OwnerManagementController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OwnerSubscriptionController;
use App\Http\Controllers\Api\PublicContentController;
use App\Http\Controllers\Api\PublicSalonController;
use App\Http\Controllers\Api\SalonRatingController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

// Admin routes
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [AdminDashboardController::class, 'stats']);
        Route::get('/revenue-chart', [AdminDashboardController::class, 'revenueChart']);
        Route::get('/visits-chart', [AdminDashboardController::class, 'visitsChart']);
        Route::get('/subscription-chart', [AdminDashboardController::class, 'subscriptionChart']);
        Route::get('/top-packages', [AdminDashboardController::class, 'topPackages']);
    });
    // Management
    Route::get('/bookings', [AdminManagementController::class, 'bookings']);
    Route::patch('/bookings/{id}/status', [AdminManagementController::class, 'updateBookingStatus']);
    Route::get('/salons', [AdminManagementController::class, 'salons']);
    Route::get('/users', [AdminManagementController::class, 'users']);
    Route::patch('/users/{id}/approve', [AdminManagementController::class, 'approveUser']);
    Route::delete('/users/{id}', [AdminManagementController::class, 'rejectUser']);
    Route::get('/subscriptions', [AdminManagementController::class, 'subscriptions']);
    Route::patch('/subscriptions/{id}/status', [AdminManagementController::class, 'updateSubscriptionStatus']);
    Route::get('/packages',            [AdminManagementController::class, 'packages']);
    Route::post('/packages',           [AdminManagementController::class, 'storePackage']);
    Route::put('/packages/{id}',       [AdminManagementController::class, 'updatePackage']);
    Route::delete('/packages/{id}',    [AdminManagementController::class, 'destroyPackage']);

    Route::get('/categories',          [AdminManagementController::class, 'categories']);
    Route::post('/categories',         [AdminManagementController::class, 'storeCategory']);
    Route::put('/categories/{id}',     [AdminManagementController::class, 'updateCategory']);
    Route::delete('/categories/{id}',  [AdminManagementController::class, 'destroyCategory']);

    Route::get('/departments',         [AdminManagementController::class, 'departments']);
    Route::post('/departments',        [AdminManagementController::class, 'storeDepartment']);
    Route::put('/departments/{id}',    [AdminManagementController::class, 'updateDepartment']);
    Route::delete('/departments/{id}', [AdminManagementController::class, 'destroyDepartment']);
});

// Owner routes
Route::middleware(['auth:sanctum', 'role:owner', 'approved', 'subscription.active'])->prefix('owner')->group(function () {
    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [OwnerDashboardController::class, 'stats']);
        Route::get('/revenue-chart', [OwnerDashboardController::class, 'revenueChart']);
        Route::get('/top-services', [OwnerDashboardController::class, 'topServices']);
        Route::get('/bookings-by-status', [OwnerDashboardController::class, 'bookingsByStatus']);
        Route::get('/salons', [OwnerDashboardController::class, 'salons']);
        Route::get('/calendar', [OwnerDashboardController::class, 'calendar']);
        Route::get('/analytics', [OwnerDashboardController::class, 'analytics']);
        Route::get('/daily-revenue', [OwnerDashboardController::class, 'dailyRevenue']);
        Route::get('/monthly-revenue', [OwnerDashboardController::class, 'monthlyRevenue']);
        Route::get('/profit-breakdown', [OwnerDashboardController::class, 'profitBreakdown']);
        Route::get('/profit-by-month', [OwnerDashboardController::class, 'profitByMonth']);
    });
    // Management — Salons
    Route::get('/salons',            [OwnerManagementController::class, 'salons']);
    Route::post('/salons',           [OwnerManagementController::class, 'storeSalon']);
    Route::get('/salons/{id}',       [OwnerManagementController::class, 'salon']);
    Route::put('/salons/{id}',       [OwnerManagementController::class, 'updateSalon']);
    Route::delete('/salons/{id}',    [OwnerManagementController::class, 'destroySalon']);
    // Management — Bookings
    Route::get('/bookings',              [OwnerManagementController::class, 'bookings']);
    Route::post('/bookings',             [OwnerManagementController::class, 'storeBooking']);
    Route::put('/bookings/{id}',         [OwnerManagementController::class, 'updateBooking']);
    Route::delete('/bookings/{id}',      [OwnerManagementController::class, 'destroyBooking']);
    Route::patch('/bookings/{id}/status',[OwnerManagementController::class, 'updateBookingStatus']);
    // Management — Services
    Route::get('/services',          [OwnerManagementController::class, 'services']);
    Route::post('/services',         [OwnerManagementController::class, 'storeService']);
    Route::put('/services/{id}',     [OwnerManagementController::class, 'updateService']);
    Route::delete('/services/{id}',  [OwnerManagementController::class, 'destroyService']);
    Route::get('/departments',       [OwnerManagementController::class, 'departments']);
    Route::get('/categories',        [OwnerManagementController::class, 'categories']);
    Route::get('/limits',            [OwnerManagementController::class, 'limits']);

    // Subscription management
    Route::get('/subscription/packages',            [OwnerSubscriptionController::class, 'packages']);
    Route::post('/subscription/subscribe/{package}', [OwnerSubscriptionController::class, 'subscribe']);
    Route::get('/subscription/history',              [OwnerSubscriptionController::class, 'history']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/stream', [NotificationController::class, 'stream']);
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
    Route::delete('/notifications', [NotificationController::class, 'destroyAll']);

    Route::prefix('customer')->group(function () {
        Route::get('/stats', [CustomerController::class, 'stats']);
        Route::get('/bookings/upcoming', [CustomerController::class, 'upcomingBookings']);
        Route::get('/bookings/history', [CustomerController::class, 'bookingHistory']);
        Route::patch('/bookings/{id}/cancel', [CustomerController::class, 'cancelBooking']);
        Route::get('/profile', [CustomerController::class, 'profile']);
        Route::put('/profile', [CustomerController::class, 'updateProfile']);

        Route::get('/favorites', [CustomerController::class, 'getFavorites']);
        Route::post('/favorites/{salonId}/toggle', [CustomerController::class, 'toggleFavorite']);
        Route::get('/favorites/{salonId}/check', [CustomerController::class, 'checkFavorite']);
    });

    Route::post('/image-cycles', [PublicContentController::class, 'storeImageCycle']);
});

Route::get('/salons/{salonId}/bookings', [BookingController::class, 'getSalonBookings']);
Route::post('/bookings/my-bookings', [BookingController::class, 'getUserBookings']);

Route::get('/salons', [PublicSalonController::class, 'index']);
Route::get('/salons/simple', [PublicSalonController::class, 'simpleList']);
Route::get('/salons/{salon}', [PublicSalonController::class, 'show']);
Route::get('/salons/{salon}/services', [PublicSalonController::class, 'services']);
Route::get('/salons/{salon}/working-hours', [PublicSalonController::class, 'workingHours']);
Route::get('/salons/{salon}/booked-times', [PublicSalonController::class, 'bookedTimes']);
Route::get('/bookings/{salon}/{date}', [PublicSalonController::class, 'bookedTimesLegacy']);

Route::post('/bookings', [BookingApiController::class, 'store']);
Route::get('/bookings/count', [BookingApiController::class, 'count']);

Route::get('/salons/{salon}/can-rate', [SalonRatingController::class, 'canRate']);
Route::get('/salons/{salon}/ratings', [SalonRatingController::class, 'index']);
Route::post('/salons/{salon}/ratings', [SalonRatingController::class, 'store']);

Route::get('/departments', [PublicContentController::class, 'departments']);
Route::get('/stats', [PublicContentController::class, 'stats']);
Route::post('/site-visits', [PublicContentController::class, 'recordVisit']);
Route::get('/service-pages', [PublicContentController::class, 'servicePages']);
Route::get('/dealspages', [PublicContentController::class, 'dealsPages']);
Route::get('/image-cycles', [PublicContentController::class, 'imageCycles']);
Route::get('/team-members', [PublicContentController::class, 'teamMembers']);

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/google', [AuthController::class, 'googleAuth']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});
