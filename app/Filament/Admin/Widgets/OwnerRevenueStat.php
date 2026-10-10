<?php

namespace App\Filament\Admin\Widgets;

use App\Models\AdminShare;
use App\Models\Booking;
use App\Models\Salon;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\StatsOverviewWidget;

class OwnerRevenueStat extends StatsOverviewWidget
{
    public function getStats(): array
    {
        $user = Filament::auth()->user();

        // Admin should NOT see owner stat
        if ($user?->hasRole('admin')) {
            return [];
        }

        // Owner salons
        $salonIds = Salon::where('created_by', $user->id)->pluck('id');

        // If no salons, return empty
        if ($salonIds->isEmpty()) {
            return [
                Stat::make('No Salons', 'You don\'t own any salons yet')
                    ->color('gray'),
            ];
        }

        // Calculate total revenue from approved bookings for owner's salons
        $totalRevenue = Booking::query()
            ->where('status', 'approved')
            ->whereIn('salon_id', $salonIds)
            ->with('service')
            ->get()
            ->sum(fn ($booking) => $booking->service?->price ?? 0);

        // Admin share percent
        $percent = (float) env('ADMIN_SHARE_PERCENT', 5);
        $adminShare = ($totalRevenue * $percent) / 100;
        $ownerShare = $totalRevenue - $adminShare;

        // Get total paid amount from admin_shares table
        $totalPaid = AdminShare::whereIn('salon_id', $salonIds)->sum('paid');
        
        $balanceOwed = $adminShare - $totalPaid;

        return [
        /*    Stat::make('Total Revenue', 'LKR ' . number_format($totalRevenue, 2))
                ->description('From approved bookings')
                ->color('primary'),

            Stat::make('Owner Share', 'LKR ' . number_format($ownerShare, 2))
                ->description('Your earnings after admin share')
                ->color('success'),

            Stat::make('Admin Balance', 'LKR ' . number_format($balanceOwed, 2))
                ->description(
                    $balanceOwed > 0 
                        ? 'Remaining to pay (' . rtrim(rtrim((string)$percent, '0'), '.') . '% of revenue)'
                        : 'Fully paid'
                )
                ->color($balanceOwed > 0 ? 'danger' : 'success')
                ->icon($balanceOwed > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'),

            Stat::make('Already Paid', 'LKR ' . number_format($totalPaid, 2))
                ->description('Amount transferred to admin')
                ->color('info')
                ->icon('heroicon-o-banknotes'), */
        ];
    }
}