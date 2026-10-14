<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

if (config('operations.featured_cleanup_enabled')) {
    Schedule::command('featured:purge-expired --apply')->hourly()->withoutOverlapping();
}

if (config('operations.subscription_reminders_enabled')) {
    Schedule::command('subscriptions:notify-expiring --send')
        ->dailyAt('09:00')->timezone('Asia/Colombo')->withoutOverlapping();
}

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
