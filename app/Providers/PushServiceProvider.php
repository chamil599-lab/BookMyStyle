<?php

namespace App\Providers;

use App\Jobs\SendBookingPush;
use App\Models\PushDevice;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingCreatedNotification;
use App\Notifications\BookingReminderNotification;
use App\Notifications\BookingStatusChangedNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;

class PushServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(NotificationSent::class, function (NotificationSent $event) {
            if (! config('push.enabled') || $event->channel !== 'database') {
                return;
            }
            $allowed = [BookingCreatedNotification::class,
                BookingStatusChangedNotification::class,
                BookingCancelledNotification::class,
                BookingReminderNotification::class];
            if (! in_array($event->notification::class, $allowed, true)) {
                return;
            }
            $booking = $event->notification->booking;
            PushDevice::where('user_id', $event->notifiable->id)->get()->each(
                fn ($device) => SendBookingPush::dispatch($device->id, $booking->id, $device->user_id, $device->personal_access_token_id,
                    $event->notification instanceof BookingReminderNotification)->afterCommit()
            );
        });
        $this->app->booted(function () {
            if (config('push.reminders_enabled')) {
                Schedule::command('bookings:remind')->everyFiveMinutes()->withoutOverlapping();
            }
        });
    }
}
