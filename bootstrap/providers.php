<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\PushServiceProvider;
use App\Providers\VoltServiceProvider;

return [
    PushServiceProvider::class,
    AppServiceProvider::class,
    AdminPanelProvider::class,
    VoltServiceProvider::class,
];
