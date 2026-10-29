<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\UserSubscription;
use App\Support\BookingValue;
use App\Support\ManagementAccess;
use App\Support\SubscriptionSummary;
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
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return ManagementAccess::admin($user?->fresh());
    }

    public function getSubscriptionStatsProperty(): array
    {
        abort_unless(static::canAccess(), 403);

        return app(SubscriptionSummary::class)->counts();
    }

    public function getRevenueStatsProperty(): array
    {
        abort_unless(static::canAccess(), 403);
        $subscriptionRevenue = UserSubscription::whereIn('status', ['active', 'expired', 'canceled'])->sum('amount_paid');

        $bookingRevenue = BookingValue::total(BookingValue::query());

        $todayBookingRevenue = BookingValue::total(BookingValue::query()->whereDate('date', today()));

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
            ->where('starts_at', '<', now()->startOfMonth()->addMonth())
            ->toBase()
            ->selectRaw('SUBSTR(starts_at, 1, 7) as month, SUM(amount_paid) as total')
            ->groupByRaw('SUBSTR(starts_at, 1, 7)')
            ->pluck('total', 'month');

        $months = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->startOfMonth()->subMonthsNoOverflow($i);
            $key = $date->format('Y-m');
            $months[] = $date->format('M Y');
            $data[] = (float) ($subscriptions[$key] ?? 0);
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

        return BookingValue::chart(BookingValue::query(), today()->subDays(29), today());
    }

    public function getTopPackagesProperty(): Collection
    {
        abort_unless(static::canAccess(), 403);

        return UserSubscription::query()
            ->active()
            ->select('package_id')
            ->selectRaw('COUNT(*) as subscription_count, SUM(amount_paid) as payment_total')
            ->groupBy('package_id')
            ->orderByDesc('subscription_count')
            ->limit(5)
            ->with('package')
            ->get()
            ->map(function ($subscription) {
                return [
                    'name' => $subscription->package?->name ?? 'Unknown',
                    'count' => (int) $subscription->subscription_count,
                    'revenue' => (float) $subscription->payment_total,
                ];
            })
            ->values();
    }

    public function getOverviewProperty(): array
    {
        abort_unless(static::canAccess(), 403);
        $visits = SiteVisit::query();

        return [
            'owners' => User::role('owner')->count(),
            'pending_owners' => User::role('owner')->where('is_approved', false)->count(),
            'visits' => (clone $visits)->count(),
            'today_visits' => (clone $visits)->whereDate('occurred_at', today())->count(),
            'month_visits' => (clone $visits)->where('occurred_at', '>=', now()->startOfMonth())
                ->where('occurred_at', '<', now()->startOfMonth()->addMonth())->count(),
            'unique_visitors' => (clone $visits)->whereNotNull('visitor_id_hash')->distinct()->count('visitor_id_hash'),
        ];
    }
}
