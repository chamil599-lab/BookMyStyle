<?php

use App\Http\Controllers\Api\V1\MobileOwnerLoginController;
use App\Http\Middleware\PrivateAccountResponse;
use Illuminate\Support\Facades\Route;

Route::get('/mobile-owner-app-access', [MobileOwnerLoginController::class, 'show'])->middleware([PrivateAccountResponse::class, 'throttle:30,1'])->name('mobile-owner-approval');
Route::post('/mobile-owner-app-access', [MobileOwnerLoginController::class, 'approve'])->middleware([PrivateAccountResponse::class, 'auth', 'verified', 'role:owner', 'throttle:6,1']);
