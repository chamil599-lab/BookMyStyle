<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Notifications\BookingReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:remind';

    protected $description = 'Notify registered customers of approved appointments within 24 hours';

    public function handle(): int
    {
        if (! config('push.reminders_enabled')) {
            $this->info('Booking reminders are disabled.');

            return self::SUCCESS;
        }
        $now = now('Asia/Colombo');
        Booking::where('status', 'approved')->whereNotNull('user_id')
            ->whereBetween('date', [$now->toDateString(), $now->copy()->addDay()->toDateString()])
            ->chunkById(100, function ($bookings) use ($now) {
                foreach ($bookings as $booking) {
                    DB::transaction(function () use ($booking, $now) {
                        $record = Booking::whereKey($booking->id)->lockForUpdate()->first();
                        if (! $record || $record->status !== 'approved' || ! $record->user) {
                            return;
                        }
                        $at = Carbon::parse($record->date->format('Y-m-d').' '.$record->time, 'Asia/Colombo');
                        if ($at->lte($now) || $at->gt($now->copy()->addDay())) {
                            return;
                        }
                        if (DB::table('booking_reminder_deliveries')->insertOrIgnore([
                            'booking_id' => $record->id, 'scheduled_at' => $at->format('Y-m-d H:i:s'),
                        ])) {
                            $record->user->notify(new BookingReminderNotification($record));
                        }
                    });
                }
            });

        return self::SUCCESS;
    }
}
