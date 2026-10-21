<?php

use App\Http\Controllers\Api\V1\MobileAdminLoginController;
use App\Http\Middleware\PrivateAccountResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/mobile-admin-login')->middleware([PrivateAccountResponse::class])->group(function () {
    Route::post('start', [MobileAdminLoginController::class, 'start'])->middleware('throttle:5,1');
    Route::post('poll', [MobileAdminLoginController::class, 'poll'])->middleware('throttle:30,1');
    Route::post('cancel', [MobileAdminLoginController::class, 'cancel'])->middleware('throttle:10,1');
});
