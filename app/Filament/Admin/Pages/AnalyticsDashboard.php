<?php

namespace App\Filament\Admin\Pages;

use App\Models\AdminShare;
use App\Models\Booking;
use App\Models\Salon;
use App\RolesEnum;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AnalyticsDashboard extends Page
{
    protected string $view = 'filament.admin.pages.analytics-dashboard';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Analytics';

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 10;

    public string $period = '30d';

    public ?int $salonId = null;

    public int $profitYear;

    public int $profitMonth;

    public static function canAccess(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        if (\App\Support\ManagementAccess::admin($user)) {
            return true;
        }

        $subscription = $user?->activeSubscription;

        return \App\Support\ManagementAccess::owner($user, true) && $subscription?->package?->analytics;
    }

    public function mount(): void
    {
        if (! static::canAccess()) {
            Notification::make()
                ->title('Analytics is not available in your current plan.')
                ->body('Please upgrade your subscription to access analytics.')
                ->danger()
                ->send();

            $this->redirect(url()->previous());

            return;
        }

        $this->profitYear = (int) now()->year;
        $this->profitMonth = (int) now()->month;
    }

    public function updatedPeriod(): void {}

    public function updatedSalonId(): void {}

    public function updatedProfitYear(): void {}

    public function updatedProfitMonth(): void {}

    public function getOwnerSalonIds(): Collection
    {
        abort_unless(static::canAccess(), 403);
        $user = Filament::auth()->user();

        if ($user?->hasRole(RolesEnum::ADMIN->value)) {
            if ($this->salonId) {
                return collect([$this->salonId]);
            }

            return Salon::pluck('id');
        }

        $ids = Salon::where('created_by', $user->id)->pluck('id');

        if ($this->salonId && $ids->contains($this->salonId)) {
            return collect([$this->salonId]);
        }

        return $ids;
    }

    public function getOwnerSalonsProperty(): Collection
    {
        $user = Filament::auth()->user();

        if ($user?->hasRole(RolesEnum::ADMIN->value)) {
            return Salon::select('id', 'name')->get();
        }

        return Salon::where('created_by', $user->id)->select('id', 'name')->get();
    }

    public function getPeriodStartDate(): ?Carbon
    {
        return match ($this->period) {
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            '90d' => now()->subDays(90),
            '12m' => now()->subMonths(12),
            default => null,
        };
    }

    public function getRevenueStatsProperty(): array
    {
        $salonIds = $this->getOwnerSalonIds();

        $allBookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->with('service')
            ->get();

        $totalRevenue = $allBookings->sum(fn ($b) => $b->service?->price ?? 0);

        $todayRevenue = $allBookings
            ->filter(fn ($b) => $b->created_at->isToday())
            ->sum(fn ($b) => $b->service?->price ?? 0);

        $periodStart = $this->getPeriodStartDate();
        $periodBookings = $periodStart
            ? $allBookings->filter(fn ($b) => $b->date->gte($periodStart))
            : $allBookings;

        $periodRevenue = $periodBookings->sum(fn ($b) => $b->service?->price ?? 0);

        return [
            'total' => $totalRevenue,
            'today' => $todayRevenue,
            'period' => $periodRevenue,
        ];
    }

    public function getBookingStatsProperty(): array
    {
        $salonIds = $this->getOwnerSalonIds();
        $periodStart = $this->getPeriodStartDate();

        $query = Booking::query()->whereIn('salon_id', $salonIds);

        if ($periodStart) {
            $query->where('date', '>=', $periodStart);
        }

        $bookings = $query->get();
        $total = $bookings->count();

        $currentMonthCount = Booking::query()
            ->whereIn('salon_id', $salonIds)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->count();

        $user = Filament::auth()->user();
        $maxPerMonth = null;

        if (! $user?->hasRole(RolesEnum::ADMIN->value)) {
            $maxPerMonth = $user?->activeSubscription?->package?->max_bookings_per_month;
        }

        return [
            'total' => $total,
            'approved' => $bookings->where('status', 'approved')->count(),
            'pending' => $bookings->where('status', 'pending')->count(),
            'rejected' => $bookings->where('status', 'rejected')->count(),
            'current_month' => $currentMonthCount,
            'max_per_month' => $maxPerMonth,
        ];
    }

    public function getMonthlyRevenueDataProperty(): array
    {
        $salonIds = $this->getOwnerSalonIds();

        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->where('date', '>=', now()->subMonths(11)->startOfMonth())
            ->with('service')
            ->get();

        $months = [];
        $revenues = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $key = $date->format('Y-m');
            $months[] = $date->format('M Y');

            $revenues[] = $bookings
                ->filter(fn ($b) => $b->date->format('Y-m') === $key)
                ->sum(fn ($b) => $b->service?->price ?? 0);
        }

        return [
            'labels' => $months,
            'data' => $revenues,
        ];
    }

    public function getDailyRevenueDataProperty(): array
    {
        $salonIds = $this->getOwnerSalonIds();
        $periodStart = $this->getPeriodStartDate() ?? now()->subDays(30);
        $days = (int) $periodStart->diffInDays(now());

        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->where('date', '>=', $periodStart)
            ->with('service')
            ->get();

        $labels = [];
        $data = [];

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M d');

            $data[] = $bookings
                ->filter(fn ($b) => $b->date->format('Y-m-d') === $key)
                ->sum(fn ($b) => $b->service?->price ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    public function getTopServicesProperty(): Collection
    {
        $salonIds = $this->getOwnerSalonIds();
        $periodStart = $this->getPeriodStartDate();

        $query = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->with('service');

        if ($periodStart) {
            $query->where('date', '>=', $periodStart);
        }

        return $query->get()
            ->groupBy('service_id')
            ->map(function ($group) {
                $service = $group->first()->service;

                return [
                    'name' => $service?->title ?? 'Unknown',
                    'bookings' => $group->count(),
                    'revenue' => $group->sum(fn ($b) => $b->service?->price ?? 0),
                ];
            })
            ->sortByDesc('bookings')
            ->take(5)
            ->values();
    }

    public function getFilteredProfitProperty(): array
    {
        $salonIds = $this->getOwnerSalonIds();

        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->whereYear('date', $this->profitYear)
            ->whereMonth('date', $this->profitMonth)
            ->with(['service', 'salon'])
            ->get();

        $totalRevenue = $bookings->sum(fn ($b) => $b->service?->price ?? 0);

        $bySalon = $bookings
            ->groupBy('salon_id')
            ->map(function ($group) {
                $salon = $group->first()->salon;

                return [
                    'name' => $salon?->name ?? 'Unknown',
                    'revenue' => $group->sum(fn ($b) => $b->service?->price ?? 0),
                    'bookings' => $group->count(),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        return [
            'total_revenue' => $totalRevenue,
            'by_salon' => $bySalon,
        ];
    }

    public function getProfitYearsProperty(): array
    {
        $earliest = Booking::min('date');
        $startYear = $earliest ? Carbon::parse($earliest)->year : now()->year;
        $years = [];
        for ($y = now()->year; $y >= $startYear; $y--) {
            $years[] = $y;
        }

        return $years;
    }

    public function getAverageRatingProperty(): float
    {
        $salonIds = $this->getOwnerSalonIds();

        return (float) Salon::whereIn('id', $salonIds)
            ->withAvg('ratings', 'rating')
            ->get()
            ->avg('ratings_avg_rating') ?? 0;
    }

    public function getProfitBreakdownProperty(): array
    {
        $salonIds = $this->getOwnerSalonIds();

        // Get bookings for the selected period
        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->with(['service', 'salon'])
            ->get();

        $totalRevenue = $bookings->sum(fn ($b) => $b->service?->price ?? 0);

        // Get admin commission percentage (default 20%)
        $adminPercent = 20;

        // Calculate admin commission and owner earnings
        $adminCommission = $totalRevenue * ($adminPercent / 100);
        $ownerEarnings = $totalRevenue - $adminCommission;

        // Get admin shares (what's owed to/from salon owners)
        $adminShares = AdminShare::whereIn('salon_id', $salonIds)->get();
        $totalOwed = $adminShares->sum('amount');
        $totalPaid = $adminShares->sum('paid');
        $balanceOwed = $totalOwed - $totalPaid;

        return [
            'total_revenue' => $totalRevenue,
            'admin_percent' => $adminPercent,
            'admin_commission' => $adminCommission,
            'owner_earnings' => $ownerEarnings,
            'total_paid' => $totalPaid,
            'balance_owed' => $balanceOwed,
        ];
    }
}
