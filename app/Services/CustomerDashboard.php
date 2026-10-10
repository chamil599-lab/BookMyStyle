<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CustomerDashboard
{
    public function bookings(User $user): Builder
    {
        return Booking::forCustomer($user)->with(['salon', 'service' => fn ($query) => $query->withTrashed()]);
    }

    public function upcoming(User $user): Builder
    {
        return $this->bookings($user)->whereIn('status', ['pending', 'approved'])
            ->where(fn ($query) => $query->whereDate('date', '>', today()->toDateString())
                ->orWhere(fn ($query) => $query->whereDate('date', today()->toDateString())->where('time', '>', now()->format('H:i:s'))))
            ->orderBy('date')->orderBy('time')->orderBy('id');
    }

    public function history(User $user): Builder
    {
        // The complement of upcoming includes terminal bookings and appointments earlier today.
        $upcoming = $this->upcoming($user)->reorder()->select('bookings.id');

        return $this->bookings($user)->whereNotIn('bookings.id', $upcoming)
            ->orderByDesc('date')->orderByDesc('time')->orderByDesc('id');
    }

    public function stats(User $user): array
    {
        return [
            'total_bookings' => $this->bookings($user)->count(),
            'upcoming_bookings' => $this->upcoming($user)->count(),
            'completed_bookings' => $this->bookings($user)->where('status', 'completed')->count(),
            // The existing schema cannot distinguish customer cancellation from salon rejection.
            'cancelled_bookings' => $this->bookings($user)->where('status', 'rejected')->count(),
            'favorite_salons' => app(FavoriteService::class)->salons($user)->count(),
        ];
    }
}
