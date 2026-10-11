<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->booking->status;
        $salonName = $this->booking->salon?->name ?? 'the salon';
        $date = $this->booking->date instanceof \DateTimeInterface
            ? $this->booking->date->format('M d, Y')
            : (string) $this->booking->date;
        $time = $this->booking->time;

        $messageMap = [
            'approved' => "Your booking at {$salonName} on {$date} at {$time} was approved.",
            'rejected' => "Your booking at {$salonName} on {$date} at {$time} was rejected.",
            'completed' => "Your booking at {$salonName} on {$date} at {$time} is marked completed.",
            'pending' => "Your booking at {$salonName} is pending review.",
        ];

        return [
            'booking_id' => $this->booking->id,
            'status' => $status,
            'salon_name' => $salonName,
            'date' => $date,
            'time' => $time,
            'title' => 'Booking '.ucfirst($status),
            'message' => $notifiable->hasRole('owner') || $notifiable->hasRole('admin')
                ? "Booking for {$this->booking->name} at {$salonName} on {$date} at {$time} is {$status}."
                : $messageMap[$status] ?? "Your booking status changed to {$status}.",
            'link' => $notifiable->hasRole('owner') || $notifiable->hasRole('admin') ? '/filament/bookings' : '/dashboard/bookings',
        ];
    }
}
