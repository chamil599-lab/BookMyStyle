<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\User;
use App\Support\BookingValue;
use App\Support\ManagementAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class OwnerDashboard extends Page
{
    protected string $view = 'filament.admin.pages.owner-dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public static function canAccess(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return ManagementAccess::owner($user?->fresh());
    }

    public function getOwnerStatsProperty(): array
    {
        abort_unless(static::canAccess(), 403);
        $user = Filament::auth()->user()->fresh();
        $salonIds = Salon::where('created_by', $user->id)->pluck('id');

        $totalEarnings = BookingValue::total(BookingValue::query($salonIds));
        $totalBookings = Booking::whereIn('salon_id', $salonIds)->count();

        return [
            'total_earnings' => $totalEarnings,
            'total_bookings' => $totalBookings,
            'salons' => $salonIds->count(),
            'pending' => Booking::whereIn('salon_id', $salonIds)->where('status', 'pending')->count(),
            'completed' => Booking::whereIn('salon_id', $salonIds)->where('status', 'completed')->count(),
            'month_bookings' => $user->getMonthlyBookingsCount(),
            'subscription' => $user->activeSubscription()->with('package')->first(),
        ];
    }
}
