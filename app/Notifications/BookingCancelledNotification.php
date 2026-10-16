<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Booking $booking,
        public string $cancelledBy = 'customer',
        public ?string $audience = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $salonName = $this->booking->salon?->name ?? 'the salon';
        $date = $this->booking->date instanceof \DateTimeInterface
            ? $this->booking->date->format('M d, Y')
            : (string) $this->booking->date;
        $time = $this->booking->time;

        $audience = $this->audience ?? 'owner';
        $link = $audience === 'customer' ? '/dashboard/bookings' : '/owner/bookings';

        $message = $this->cancelledBy === 'customer'
            ? "Customer {$this->booking->name} cancelled their booking on {$date} at {$time}."
            : "Your booking at {$salonName} on {$date} at {$time} was cancelled.";

        return [
            'booking_id' => $this->booking->id,
            'salon_name' => $salonName,
            'cancelled_by' => $this->cancelledBy,
            'date' => $date,
            'time' => $time,
            'title' => 'Booking cancelled',
            'message' => $message,
            'link' => $link,
        ];
    }
}
