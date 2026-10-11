<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Support\ManagementAccess;
use Illuminate\Support\Facades\Gate;

class BookingWhatsApp
{
    public function phone(?string $phone): ?string
    {
        if (! $phone || ! preg_match('/^[+0-9 ()\-.]+$/D', $phone)) {
            return null;
        }
        $digits = preg_replace('/\D/', '', $phone);
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '94'.substr($digits, 1);
        }

        return preg_match('/^[1-9][0-9]{7,14}$/D', $digits) ? $digits : null;
    }

    public function url(User $actor, Booking $booking): ?string
    {
        $actor = $actor->fresh();
        abort_unless(ManagementAccess::admin($actor) || ManagementAccess::owner($actor, true), 403);
        Gate::forUser($actor)->authorize('view', $booking);
        if (! $phone = $this->phone($booking->phone)) {
            return null;
        }
        $booking->loadMissing(['salon', 'service' => fn ($query) => $query->withTrashed()]);
        $service = $booking->service?->title ?? 'your service';
        $salon = $booking->salon?->name ?? 'the salon';
        $status = in_array($booking->status, ['pending', 'approved', 'rejected', 'completed'], true) ? $booking->status : 'updated';
        $message = "Hello {$booking->name},\n\nYour booking for {$service} is {$status}.\n\nDate: {$booking->date->format('F d, Y')}\nTime: {$booking->time}\nSalon: {$salon}\n\nPlease contact the salon for any changes.";

        return 'https://wa.me/'.$phone.'?'.http_build_query(['text' => $message], '', '&', PHP_QUERY_RFC3986);
    }
}
