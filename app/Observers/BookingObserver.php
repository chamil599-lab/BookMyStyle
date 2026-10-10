<?php

namespace App\Observers;

use App\Mail\BookingStatusChanged;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingObserver implements \Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit
{
    public function created(Booking $booking): void
    {
        try {
            $booking->loadMissing(['salon.owner', 'service']);
            $booking->salon?->owner?->notify(new \App\Notifications\BookingCreatedNotification($booking));
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
                $booking->user?->notify(new \App\Notifications\BookingStatusChangedNotification($booking));
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
