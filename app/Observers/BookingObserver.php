<?php

namespace App\Observers;

use App\Mail\BookingStatusChanged;
use App\Models\Booking;
use App\Notifications\BookingCreatedNotification;
use App\Notifications\BookingStatusChangedNotification;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingObserver implements ShouldHandleEventsAfterCommit
{
    public function created(Booking $booking): void
    {
        try {
            $booking->loadMissing(['salon.owner', 'service']);
            $booking->salon?->owner?->notify(new BookingCreatedNotification($booking));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    public function updated(Booking $booking): void
    {
        // Check if status was changed
        if ($booking->wasChanged('status')) {
            try {
                $booking->loadMissing(['salon', 'service', 'user']);
                $booking->user?->notify(new BookingStatusChangedNotification($booking));
            } catch (\Throwable $exception) {
                report($exception);
            }
            $newStatus = $booking->status;

            // Send email only for approved or rejected status
            if (in_array($newStatus, [Booking::STATUS_APPROVED, Booking::STATUS_REJECTED])) {
                try {
                    Mail::to($booking->email)->send(new BookingStatusChanged($booking));
                    Log::info("Booking status email sent for booking #{$booking->id}");
                } catch (\Exception $e) {
                    Log::error('Failed to send booking email: '.$e->getMessage());
                }
            }
        }
    }
}
