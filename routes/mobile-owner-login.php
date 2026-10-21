<?php

use App\Http\Controllers\Api\V1\MobileOwnerLoginController;
use App\Http\Middleware\PrivateAccountResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/mobile-owner-login')->middleware([PrivateAccountResponse::class])->group(function () {
    Route::post('start', [MobileOwnerLoginController::class, 'start'])->middleware('throttle:5,1');
    Route::post('poll', [MobileOwnerLoginController::class, 'poll'])->middleware('throttle:30,1');
    Route::post('cancel', [MobileOwnerLoginController::class, 'cancel'])->middleware('throttle:10,1');
});
