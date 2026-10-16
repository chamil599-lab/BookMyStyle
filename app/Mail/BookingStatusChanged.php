<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function envelope(): Envelope
    {
        $status = $this->booking->status === 'approved' ? 'Confirmed' : 'Rejected';

        return new Envelope(
            subject: "Your Booking Has Been {$status} - BookMyStyle",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.booking-status-changed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
