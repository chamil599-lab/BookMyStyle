<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Notifications\Notification;

class BookingReminderNotification extends Notification
{
    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['booking_id' => $this->booking->id, 'title' => 'Appointment reminder',
            'message' => 'You have an approved appointment within the next 24 hours. Check your booking for the time and salon.',
            'link' => '/dashboard/upcoming'];
    }
}
