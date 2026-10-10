<?php

namespace App\Services;

use App\Models\AdminShare;
use App\Models\Booking;

class AdminShareCalculator
{
    /**
     * Total 5% commission from approved bookings
     */
    public static function totalCommission(): float
    {
        return Booking::query()
            ->where('status', 'approved')
            ->with('service')
            ->get()
            ->sum(function ($booking) {
                return ($booking->service?->price ?? 0) * 0.05;
            });
    }

    /**
     * Total paid by all salons (from admin_shares table)
     */
    public static function totalPaid(): float
    {
        return AdminShare::sum('paid');  // Changed from 'amount' to 'paid'
    }

    /**
     * Balance remaining (commission - paid)
     */
    public static function balance(): float
    {
        return self::totalCommission() - self::totalPaid();
    }
}