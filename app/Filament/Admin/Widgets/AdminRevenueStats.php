<?php

namespace App\Filament\Admin\Widgets;

use App\Services\AdminShareCalculator;
use App\RolesEnum;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminRevenueStats extends StatsOverviewWidget
{
    public function getStats(): array
    {
        // Admin only
        $user = Filament::auth()->user();
        if (! ($user?->hasRole(RolesEnum::ADMIN->value) ?? false)) {
            return [];
        }

        $totalCommission = AdminShareCalculator::totalCommission();
        $totalPaid       = AdminShareCalculator::totalPaid();
        $balance         = AdminShareCalculator::balance();

        return [
            Stat::make(
                'Total Commission (5%)',
                'LKR ' . number_format($totalCommission, 2)
            )
                ->description('Calculated from approved bookings')
                ->color('success'),

            Stat::make(
                'Total Paid by Owners',
                'LKR ' . number_format($totalPaid, 2)
            )
                ->description('Manually recorded')
                ->color('info'),

            Stat::make(
                'Admin Balance',
                ($balance > 0 ? '- LKR ' : 'LKR ') . number_format(abs($balance), 2)
            )
                ->description('Remaining to receive')
                ->color($balance > 0 ? 'danger' : 'success'),
        ];
    }
}
