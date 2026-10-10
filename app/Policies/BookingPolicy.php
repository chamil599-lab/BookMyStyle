<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\User;

class BookingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $this->isActiveOwner($user);
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->update($user, $booking);
    }

    public function create(User $user): bool
    {
        return $user->hasRole('admin') || ($this->isActiveOwner($user) && $user->canCreateMoreBookings());
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->hasRole('admin') || ($this->isActiveOwner($user)
            && Salon::whereKey($booking->salon_id)->where('created_by', $user->id)->exists());
    }

    public function updateStatus(User $user, Booking $booking): bool
    {
        return $this->update($user, $booking);
    }

    public function viewSalonBookings(User $user, Salon $salon): bool
    {
        return $user->hasRole('admin') || ($this->isActiveOwner($user) && (int) $salon->created_by === (int) $user->id);
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $this->update($user, $booking);
    }

    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user);
    }

    private function isActiveOwner(User $user): bool
    {
        return $user->hasRole('owner') && $user->is_approved && $user->hasActiveSubscription();
    }
}
