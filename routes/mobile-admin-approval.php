<?php

use App\Http\Controllers\Api\V1\MobileAdminLoginController;
use App\Http\Middleware\PrivateAccountResponse;
use Illuminate\Support\Facades\Route;

Route::get('/mobile-app-access', [MobileAdminLoginController::class, 'show'])->middleware([PrivateAccountResponse::class, 'throttle:30,1'])->name('mobile-admin-approval');
Route::post('/mobile-app-access', [MobileAdminLoginController::class, 'approve'])->middleware([PrivateAccountResponse::class, 'auth', 'verified', 'role:admin', 'throttle:6,1']);
