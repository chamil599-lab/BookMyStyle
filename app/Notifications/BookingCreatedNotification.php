<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $service = $this->booking->service?->name ?? 'a service';
        $date = $this->booking->date instanceof \DateTimeInterface
            ? $this->booking->date->format('M d, Y')
            : (string) $this->booking->date;
        $time = $this->booking->time;

        return [
            'booking_id'    => $this->booking->id,
            'salon_id'      => $this->booking->salon_id,
            'customer_name' => $this->booking->name,
            'service'       => $service,
            'date'          => $date,
            'time'          => $time,
            'title'         => 'New booking received',
            'message'       => "{$this->booking->name} booked {$service} on {$date} at {$time}.",
            'link'          => '/owner/bookings',
        ];
    }
}
