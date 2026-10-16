<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Salon;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RevenueStats extends StatsOverviewWidget
{
    public function getStats(): array
    {
        $query = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->with('service');

        // Admin sees all revenue, owners see only their salons
        if (! auth()->user()->hasRole('admin')) {
            $ownerSalonIds = Salon::where('created_by', auth()->id())->pluck('id');
            $query->whereIn('salon_id', $ownerSalonIds);
        }

        $allBookings = $query->get();
        $totalRevenue = $allBookings->sum(fn ($b) => $b->service?->price ?? 0);

        // ✅ FIX: Apply same filter to today's revenue
        $todayQuery = Booking::query()
            ->where('status', 'approved')
            ->whereDate('created_at', Carbon::today())
            ->with('service');

        // Apply owner filter to today's revenue too
        if (! auth()->user()->hasRole('admin')) {
            $todayQuery->whereIn('salon_id', $ownerSalonIds ?? Salon::where('created_by', auth()->id())->pluck('id'));
        }

        $todayRevenue = $todayQuery->get()->sum(fn ($booking) => $booking->service?->price ?? 0);

        $totalBookings = $allBookings->count();

        return [
            Stat::make('Total Revenue', 'LKR '.number_format($totalRevenue, 2))
                ->description('Approved & completed bookings')
                ->color('success')
                ->icon('heroicon-o-currency-dollar'),

            Stat::make('Today\'s Revenue', 'LKR '.number_format($todayRevenue, 2))
                ->description('Revenue from today\'s bookings')
                ->color('primary')
                ->icon('heroicon-o-sparkles'),

            Stat::make('Total Bookings', $totalBookings)
                ->description('Approved & completed bookings count')
                ->color('secondary')
                ->icon('heroicon-o-calendar'),
        ];
    }
}
