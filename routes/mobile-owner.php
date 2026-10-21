<?php

use App\Http\Controllers\Api\V1\MobileOwnerController as Owner;
use App\Http\Middleware\PrivateAccountResponse;
use Illuminate\Support\Facades\Route;

// Additive API: legacy routes and the Filament panel remain unchanged.
Route::prefix('v1/owner')->middleware([PrivateAccountResponse::class, 'auth:sanctum', 'verified', 'role:owner', 'approved', 'throttle:60,1'])->group(function () {
    Route::get('overview', [Owner::class, 'overview']);
    Route::get('lookups', [Owner::class, 'lookups']);
    Route::get('salons', [Owner::class, 'salons']);
    Route::post('salons', [Owner::class, 'saveSalon']);
    Route::patch('salons/{id}', [Owner::class, 'saveSalon'])->whereNumber('id');
    Route::delete('salons/{id}', [Owner::class, 'deleteSalon'])->whereNumber('id');
    Route::post('salons/{id}/restore', [Owner::class, 'restoreSalon'])->whereNumber('id');
    Route::post('salons/{id}/media', [Owner::class, 'media'])->whereNumber('id');
    Route::patch('salons/{id}/media-order', [Owner::class, 'reorderMedia'])->whereNumber('id');
    Route::delete('salons/{id}/media/{media}', [Owner::class, 'deleteMedia'])->whereNumber(['id', 'media']);
    Route::get('services', [Owner::class, 'services']);
    Route::post('services', [Owner::class, 'saveService']);
    Route::patch('services/{id}', [Owner::class, 'saveService'])->whereNumber('id');
    Route::delete('services/{id}', [Owner::class, 'deleteService'])->whereNumber('id');
    Route::get('bookings', [Owner::class, 'bookings']);
    Route::get('calendar', [Owner::class, 'calendar']);
    Route::post('bookings', [Owner::class, 'saveBooking']);
    Route::patch('bookings/{id}', [Owner::class, 'saveBooking'])->whereNumber('id');
    Route::delete('bookings/{id}', [Owner::class, 'deleteBooking'])->whereNumber('id');
    Route::get('slots', [Owner::class, 'slots']);
    Route::get('subscriptions', [Owner::class, 'subscriptions']);
    Route::post('subscriptions', [Owner::class, 'requestSubscription']);
    Route::get('reports', [Owner::class, 'reports']);
});
