<?php

namespace App\Filament\Admin\Pages;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\SalonRating;
use App\Support\BookingValue;
use App\Support\ManagementAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;

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

    #[Locked]
    public array $validFilters = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user()?->fresh();

        return ManagementAccess::admin($user) ||
            (ManagementAccess::owner($user, true) && $user->activeSubscription?->package?->analytics);
    }

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);
        $this->profitYear = now()->year;
        $this->profitMonth = now()->month;
        $this->validFilters = ['period' => $this->period, 'salonId' => $this->salonId, 'profitYear' => $this->profitYear, 'profitMonth' => $this->profitMonth];
    }

    public function updated(string $property): void
    {
        abort_unless(static::canAccess(), 403);
        $rules = ['period' => 'required|in:7d,30d,90d,12m,all', 'salonId' => 'nullable|integer|min:1',
            'profitYear' => 'required|integer|between:1900,2100', 'profitMonth' => 'required|integer|between:1,12'];
        abort_unless(array_key_exists($property, $rules), 422);
        $value = $this->{$property};
        $validator = Validator::make([$property => $value], [$property => $rules[$property]]);
        if ($validator->fails()) {
            $this->{$property} = $this->validFilters[$property];
            $validator->validate();
        }
        if ($property === 'salonId' && $value !== null && $value !== '') {
            abort_unless($this->ownerSalons->pluck('id')->contains((int) $value), 404);
        }
        $this->validFilters[$property] = $this->{$property};
        $this->resetErrorBag($property);
    }

    protected function validateFilters(): void
    {
        abort_unless(static::canAccess(), 403);
        Validator::make(['period' => $this->period, 'salonId' => $this->salonId,
            'profitYear' => $this->profitYear, 'profitMonth' => $this->profitMonth], [
                'period' => 'required|in:7d,30d,90d,12m,all', 'salonId' => 'nullable|integer|min:1',
                'profitYear' => 'required|integer|between:1900,2100', 'profitMonth' => 'required|integer|between:1,12',
            ])->validate();
    }

    public function getOwnerSalonsProperty(): Collection
    {
        abort_unless(static::canAccess(), 403);
        $user = Filament::auth()->user()->fresh();

        return Salon::query()->when(! ManagementAccess::admin($user), fn ($q) => $q->where('created_by', $user->id))
            ->select('id', 'name')->get();
    }

    public function getOwnerSalonIds(): Collection
    {
        $this->validateFilters();
        $ids = $this->ownerSalons->pluck('id');
        if ($this->salonId !== null) {
            abort_unless($ids->contains($this->salonId), 404);

            return collect([$this->salonId]);
        }

        return $ids;
    }

    public function getPeriodStartDate(): ?Carbon
    {
        $this->validateFilters();

        return match ($this->period) {
            '7d' => today()->subDays(6), '30d' => today()->subDays(29), '90d' => today()->subDays(89),
            '12m' => today()->startOfMonth()->subMonthsNoOverflow(11), 'all' => null,
        };
    }

    protected function periodQuery(bool $confirmed = false): Builder
    {
        $ids = $this->getOwnerSalonIds();
        $query = $confirmed ? BookingValue::query($ids) : Booking::whereIn('salon_id', $ids);
        $start = $this->getPeriodStartDate();

        return $query->where('date', '<=', today()->toDateString())
            ->when($start, fn ($q) => $q->where('date', '>=', $start->toDateString()));
    }

    public function getRevenueStatsProperty(): array
    {
        $query = BookingValue::query($this->getOwnerSalonIds());

        return [
            'total' => (clone $query)->get()->sum(fn ($b) => $b->service?->price ?? 0),
            'today' => (clone $query)->whereDate('date', today())->get()->sum(fn ($b) => $b->service?->price ?? 0),
            'period' => $this->periodQuery(true)->get()->sum(fn ($b) => $b->service?->price ?? 0),
        ];
    }

    public function getBookingStatsProperty(): array
    {
        $bookings = $this->periodQuery()->get();
        $user = Filament::auth()->user()->fresh();
        // Quotas count requests created this month, matching BookingService.
        $currentMonthCount = Booking::whereIn('salon_id', $this->ownerSalons->pluck('id'))
            ->where('created_at', '>=', now()->startOfMonth())->where('created_at', '<', now()->startOfMonth()->addMonth())->count();

        return ['total' => $bookings->count(), 'approved' => $bookings->where('status', 'approved')->count(),
            'completed' => $bookings->where('status', 'completed')->count(),
            'pending' => $bookings->where('status', 'pending')->count(), 'rejected' => $bookings->where('status', 'rejected')->count(),
            'current_month' => $currentMonthCount,
            'max_per_month' => ManagementAccess::admin($user) ? null : $user->activeSubscription?->package?->max_bookings_per_month];
    }

    public function getMonthlyRevenueDataProperty(): array
    {
        return BookingValue::chart(BookingValue::query($this->getOwnerSalonIds()), today()->startOfMonth()->subMonthsNoOverflow(11), today(), true);
    }

    public function getDailyRevenueDataProperty(): array
    {
        if ($this->period === '12m') {
            return $this->monthlyRevenueData;
        }

        return BookingValue::chart(BookingValue::query($this->getOwnerSalonIds()), $this->getPeriodStartDate() ?? today()->subDays(29), today());
    }

    public function getTopServicesProperty(): Collection
    {
        return $this->periodQuery(true)->get()->groupBy('service_id')->map(fn ($group) => [
            'name' => $group->first()->service?->title ?? 'Deleted service', 'bookings' => $group->count(),
            'revenue' => $group->sum(fn ($b) => $b->service?->price ?? 0),
        ])->sortByDesc('bookings')->take(5)->values();
    }

    public function getFilteredProfitProperty(): array
    {
        $bookings = BookingValue::query($this->getOwnerSalonIds())->whereYear('date', $this->profitYear)
            ->whereMonth('date', $this->profitMonth)->with('salon')->get();

        return ['total_revenue' => $bookings->sum(fn ($b) => $b->service?->price ?? 0),
            'by_salon' => $bookings->groupBy('salon_id')->map(fn ($group) => [
                'name' => $group->first()->salon?->name ?? 'Deleted salon', 'bookings' => $group->count(),
                'revenue' => $group->sum(fn ($b) => $b->service?->price ?? 0),
            ])->sortByDesc('revenue')->values()];
    }

    public function getProfitYearsProperty(): array
    {
        $earliest = Booking::whereIn('salon_id', $this->getOwnerSalonIds())->min('date');

        return range(now()->year, min(now()->year, max(1900, $earliest ? Carbon::parse($earliest)->year : now()->year)));
    }

    public function getAverageRatingProperty(): float
    {
        return (float) (SalonRating::whereIn('salon_id', $this->getOwnerSalonIds())->avg('rating') ?? 0);
    }
}
