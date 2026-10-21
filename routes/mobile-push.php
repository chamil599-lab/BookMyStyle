<?php

use App\Http\Controllers\Api\PushDeviceController;
use App\Http\Middleware\PrivateAccountResponse;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/push')->middleware([PrivateAccountResponse::class, 'auth:sanctum', 'throttle:20,1'])->group(function () {
    Route::put('device', [PushDeviceController::class, 'save']);
    Route::delete('device', [PushDeviceController::class, 'destroy']);
});
