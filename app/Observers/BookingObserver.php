<?php

namespace App\Observers;

use App\Mail\BookingStatusChanged;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    public function updated(Booking $booking): void
    {
        // Check if status was changed
        if ($booking->isDirty('status')) {
            $newStatus = $booking->status;
            
            // Send email only for approved or rejected status
            if (in_array($newStatus, [Booking::STATUS_APPROVED, Booking::STATUS_REJECTED])) {
                try {
                    Mail::to($booking->email)->send(new BookingStatusChanged($booking));
                    Log::info("Booking status email sent to {$booking->email} for booking #{$booking->id}");
                } catch (\Exception $e) {
                    Log::error("Failed to send booking email: " . $e->getMessage());
                }
            }
        }
    }
}