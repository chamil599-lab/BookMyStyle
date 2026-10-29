<?php

use App\Http\Controllers\Api\V1\MobileAdminController as Admin;
use App\Http\Middleware\PrivateAccountResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/admin')->middleware([PrivateAccountResponse::class, 'auth:sanctum', 'verified', 'role:admin', 'throttle:60,1'])->group(function () {
    Route::get('overview', [Admin::class, 'overview']);
    Route::get('lookups', [Admin::class, 'lookups']);
    Route::get('salons', [Admin::class, 'salons']);
    Route::post('salons', [Admin::class, 'saveSalon']);
    Route::patch('salons/{id}', [Admin::class, 'saveSalon'])->whereNumber('id');
    Route::delete('salons/{id}', [Admin::class, 'deleteSalon'])->whereNumber('id');
    Route::post('salons/{id}/restore', [Admin::class, 'restoreSalon'])->whereNumber('id');
    Route::post('salons/{id}/media', [Admin::class, 'media'])->whereNumber('id');
    Route::delete('salons/{id}/media/{media}', [Admin::class, 'deleteMedia'])->whereNumber(['id', 'media']);
    Route::patch('salons/{id}/media-order', [Admin::class, 'reorderMedia'])->whereNumber('id');
    Route::get('services', [Admin::class, 'services']);
    Route::post('services', [Admin::class, 'saveService']);
    Route::patch('services/{id}', [Admin::class, 'saveService'])->whereNumber('id');
    Route::delete('services/{id}', [Admin::class, 'deleteService'])->whereNumber('id');
    Route::get('bookings', [Admin::class, 'bookings']);
    Route::post('bookings', [Admin::class, 'saveBooking']);
    Route::patch('bookings/{id}', [Admin::class, 'saveBooking'])->whereNumber('id');
    Route::delete('bookings/{id}', [Admin::class, 'deleteBooking'])->whereNumber('id');
    Route::get('calendar', [Admin::class, 'calendar']);
    Route::get('slots', [Admin::class, 'slots']);
    Route::get('reports', [Admin::class, 'reports']);
    Route::get('subscriptions', [Admin::class, 'subscriptions']);
    Route::post('subscriptions', [Admin::class, 'requestSubscription']);
    Route::post('subscriptions/{id}/{action}', [Admin::class, 'subscriptionAction'])->whereNumber('id');
    Route::get('resources/{kind}/schema', [Admin::class, 'schema']);
    Route::get('resources/{kind}', [Admin::class, 'records']);
    Route::post('resources/{kind}', [Admin::class, 'saveRecord']);
    Route::patch('resources/{kind}/{id}', [Admin::class, 'saveRecord'])->whereNumber('id');
    Route::post('resources/{kind}/{id}/photo-upload', [Admin::class, 'contentPhoto'])->whereNumber('id');
    Route::delete('resources/{kind}/{id}/photo', [Admin::class, 'removeContentPhoto'])->whereNumber('id');
    Route::post('resources/{kind}/{id}/{action}', [Admin::class, 'recordAction'])->whereNumber('id');
});
