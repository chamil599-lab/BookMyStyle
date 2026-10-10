<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\UserSubscription;
use App\RolesEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use BackedEnum;
use Illuminate\Support\Collection;

class Dashboard extends Page
{
    protected string $view = 'filament.admin.pages.dashboard';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        return $user?->hasRole(RolesEnum::ADMIN->value) ?? false;
    }

    public function getSubscriptionStatsProperty(): array
    {
        return [
            'total' => UserSubscription::count(),
            'active' => UserSubscription::where('status', 'active')->where('ends_at', '>', now())->count(),
            'pending' => UserSubscription::where('status', 'pending')->count(),
            'expired' => UserSubscription::where(function ($q) {
                $q->where('status', 'expired')->orWhere('ends_at', '<=', now());
            })->where('status', '!=', 'canceled')->count(),
            'canceled' => UserSubscription::where('status', 'canceled')->count(),
        ];
    }

    public function getRevenueStatsProperty(): array
    {
        $subscriptionRevenue = UserSubscription::where('status', 'active')->sum('amount_paid');

        $bookingRevenue = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->with('service')
            ->get()
            ->sum(fn ($b) => $b->service?->price ?? 0);

        $todayBookingRevenue = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereDate('created_at', today())
            ->with('service')
            ->get()
            ->sum(fn ($b) => $b->service?->price ?? 0);

        $totalBookings = Booking::count();

        return [
            'subscription_revenue' => $subscriptionRevenue,
            'booking_revenue' => $bookingRevenue,
            'today_booking_revenue' => $todayBookingRevenue,
            'total_bookings' => $totalBookings,
        ];
    }

    public function getMonthlySubRevenueProperty(): array
    {
        $subscriptions = UserSubscription::where('status', 'active')
            ->where('starts_at', '>=', now()->subMonths(11)->startOfMonth())
            ->get();

        $months = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[] = $date->format('M Y');
            $data[] = $subscriptions
                ->filter(fn ($s) => $s->starts_at?->format('Y-m') === $key)
                ->sum('amount_paid');
        }

        return ['labels' => $months, 'data' => $data];
    }

    public function getSubStatusDataProperty(): array
    {
        $stats = $this->subscriptionStats;

        return [
            $stats['active'],
            $stats['pending'],
            $stats['expired'],
            $stats['canceled'],
        ];
    }

    public function getDailyBookingRevenueProperty(): array
    {
        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->where('date', '>=', now()->subDays(30))
            ->with('service')
            ->get();

        $labels = [];
        $data = [];

        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M d');
            $data[] = $bookings
                ->filter(fn ($b) => $b->date->format('Y-m-d') === $key)
                ->sum(fn ($b) => $b->service?->price ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }

    public function getTopPackagesProperty(): Collection
    {
        return UserSubscription::query()
            ->where('status', 'active')
            ->with('package')
            ->get()
            ->groupBy('package_id')
            ->map(function ($group) {
                $package = $group->first()->package;
                return [
                    'name' => $package?->name ?? 'Unknown',
                    'count' => $group->count(),
                    'revenue' => $group->sum('amount_paid'),
                ];
            })
            ->sortByDesc('count')
            ->take(5)
            ->values();
    }

}
