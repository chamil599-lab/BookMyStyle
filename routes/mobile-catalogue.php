<?php

use App\Http\Controllers\Api\MobileCatalogueController;
use Illuminate\Support\Facades\Route;

Route::get('v1/catalogue/salons', [MobileCatalogueController::class, 'index'])->middleware('throttle:60,1');
