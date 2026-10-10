<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\Salon;
use App\RolesEnum;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;

class OwnerDashboard extends Page
{
    protected string $view = 'filament.admin.pages.owner-dashboard';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        return $user?->hasRole(RolesEnum::OWNER->value) ?? false;
    }

    public function getOwnerStatsProperty(): array
    {
        $user = Filament::auth()->user();
        $salonIds = Salon::where('created_by', $user->id)->pluck('id');

        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->with('service')
            ->get();

        $totalEarnings = $bookings->sum(fn ($b) => $b->service?->price ?? 0);
        $totalBookings = $bookings->count();

        return [
            'total_earnings' => $totalEarnings,
            'total_bookings' => $totalBookings,
        ];
    }
}
