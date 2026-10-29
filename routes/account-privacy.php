<?php

use Illuminate\Support\Facades\Route;

// Public: users must be able to request deletion without installing the app.
Route::view('/delete-account', 'storefront.delete-account')->name('account.deletion');
