<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\UserSubscription;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Dashboard extends Page
{
    protected string $view = 'filament.admin.pages.dashboard';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        return \App\Support\ManagementAccess::admin($user?->fresh());
    }

    public function getSubscriptionStatsProperty(): array
    {
        abort_unless(static::canAccess(), 403);

        return app(\App\Support\SubscriptionSummary::class)->counts();
    }

    public function getRevenueStatsProperty(): array
    {
        abort_unless(static::canAccess(), 403);
        $subscriptionRevenue = UserSubscription::whereIn('status', ['active', 'expired', 'canceled'])->sum('amount_paid');

        $bookingRevenue = \App\Support\BookingValue::query()
            ->get()
            ->sum(fn ($b) => $b->service?->price ?? 0);

        $todayBookingRevenue = \App\Support\BookingValue::query()->whereDate('date', today())
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
        abort_unless(static::canAccess(), 403);
        $subscriptions = UserSubscription::whereIn('status', ['active', 'expired', 'canceled'])
            ->where('starts_at', '>=', now()->startOfMonth()->subMonthsNoOverflow(11))
            ->get();

        $months = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonthsNoOverflow($i);
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

        return array_map(fn ($status) => $stats[$status], ['active', 'scheduled', 'pending', 'expired', 'canceled', 'rejected']);
    }

    public function getDailyBookingRevenueProperty(): array
    {
        abort_unless(static::canAccess(), 403);

        return \App\Support\BookingValue::chart(\App\Support\BookingValue::query(), today()->subDays(29), today());
    }

    public function getTopPackagesProperty(): Collection
    {
        abort_unless(static::canAccess(), 403);

        return UserSubscription::query()
            ->active()
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

    public function getOverviewProperty(): array
    {
        abort_unless(static::canAccess(), 403);
        $visits = \App\Models\SiteVisit::query();

        return [
            'owners' => \App\Models\User::role('owner')->count(),
            'pending_owners' => \App\Models\User::role('owner')->where('is_approved', false)->count(),
            'visits' => (clone $visits)->count(),
            'today_visits' => (clone $visits)->whereDate('occurred_at', today())->count(),
            'month_visits' => (clone $visits)->where('occurred_at', '>=', now()->startOfMonth())
                ->where('occurred_at', '<', now()->startOfMonth()->addMonth())->count(),
            'unique_visitors' => (clone $visits)->whereNotNull('visitor_id_hash')->distinct()->count('visitor_id_hash'),
        ];
    }
}
