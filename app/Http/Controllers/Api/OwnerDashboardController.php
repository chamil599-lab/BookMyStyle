<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Salon;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OwnerDashboardController extends Controller
{
    private function ownerSalonIds(): \Illuminate\Support\Collection
    {
        return Salon::where('created_by', auth()->id())->pluck('id');
    }

    public function stats(Request $request)
    {
        $user          = auth()->user();
        $allSalonIds   = $this->ownerSalonIds();
        $salonId       = $request->query('salon_id');
        $salonIds      = ($salonId && $allSalonIds->contains($salonId))
                         ? collect([$salonId])
                         : $allSalonIds;

        // Single SQL aggregate over bookings join services: replaces N+1 load-all + PHP sums.
        $monthStart = now()->startOfMonth();
        $todayStart = now()->startOfDay();

        $row = Booking::query()
            ->leftJoin('services', 'bookings.service_id', '=', 'services.id')
            ->whereIn('bookings.salon_id', $salonIds)
            ->selectRaw("
                COALESCE(SUM(CASE WHEN bookings.status IN ('approved','completed') THEN services.price ELSE 0 END), 0) AS revenue_total,
                COALESCE(SUM(CASE WHEN bookings.status IN ('approved','completed') AND bookings.created_at >= ? THEN services.price ELSE 0 END), 0) AS revenue_this_month,
                COALESCE(SUM(CASE WHEN bookings.status IN ('approved','completed') AND bookings.created_at >= ? THEN services.price ELSE 0 END), 0) AS revenue_today,
                SUM(CASE WHEN bookings.status = 'approved' THEN 1 ELSE 0 END) AS bookings_approved,
                SUM(CASE WHEN bookings.status = 'pending' THEN 1 ELSE 0 END) AS bookings_pending,
                SUM(CASE WHEN bookings.status = 'completed' THEN 1 ELSE 0 END) AS bookings_completed,
                SUM(CASE WHEN bookings.status = 'rejected' THEN 1 ELSE 0 END) AS bookings_rejected,
                SUM(CASE WHEN bookings.status = 'approved' AND bookings.created_at >= ? THEN 1 ELSE 0 END) AS bookings_approved_this_month
            ", [$monthStart, $todayStart, $monthStart])
            ->first();

        $subscription    = $user->activeSubscription()->with('package')->first();
        $package         = $subscription?->package;
        $maxAllowedSalons = $package?->max_salons;

        return response()->json([
            'revenue' => [
                'total'      => (float) ($row->revenue_total ?? 0),
                'this_month' => (float) ($row->revenue_this_month ?? 0),
                'today'      => (float) ($row->revenue_today ?? 0),
            ],
            'bookings' => [
                'total'      => (int) ($row->bookings_approved ?? 0),
                'pending'    => (int) ($row->bookings_pending ?? 0),
                'approved'   => (int) ($row->bookings_approved ?? 0),
                'completed'  => (int) ($row->bookings_completed ?? 0),
                'rejected'   => (int) ($row->bookings_rejected ?? 0),
                'this_month' => (int) ($row->bookings_approved_this_month ?? 0),
            ],
            'salons' => [
                'count'       => $salonIds->count(),
                'max_allowed' => $maxAllowedSalons,
            ],
            'subscription' => $subscription ? [
                'package_name'   => $package?->name,
                'status'         => $subscription->status,
                'days_remaining' => max(0, $subscription->daysRemaining()),
                'ends_at'        => $subscription->ends_at?->format('Y-m-d'),
                'is_trial'       => (bool) $subscription->is_trial,
                'features' => [
                    'max_salons'             => $package?->max_salons,
                    'max_staff'              => $package?->max_staff,
                    'max_services'           => $package?->max_services,
                    'max_bookings_per_month' => $package?->max_bookings_per_month,
                    'analytics'              => (bool) $package?->analytics,
                    'priority_support'       => (bool) $package?->priority_support,
                    'calendar'               => (bool) $package?->calendar,
                ],
            ] : null,
        ]);
    }

    public function revenueChart(Request $request)
    {
        $year     = (int) ($request->query('year', now()->year));
        $salonId  = $request->query('salon_id');
        $salonIds = $this->ownerSalonIds();

        if ($salonId && $salonIds->contains((int) $salonId)) {
            $salonIds = collect([(int) $salonId]);
        }

        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->whereYear('date', $year)
            ->with('service')
            ->get();

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthKey = sprintf('%04d-%02d', $year, $m);
            $data[] = [
                'month'   => now()->setDate($year, $m, 1)->format('M'),
                'revenue' => (float) $bookings
                    ->filter(fn ($b) => $b->date->format('Y-m') === $monthKey)
                    ->sum(fn ($b) => $b->service?->price ?? 0),
            ];
        }

        return response()->json($data);
    }

    public function topServices(Request $request)
    {
        $period   = $request->query('period', '30d');
        $salonIds = $this->ownerSalonIds();

        $periodStart = match ($period) {
            '7d'  => now()->subDays(7),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };

        $data = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->where('date', '>=', $periodStart)
            ->with('service')
            ->get()
            ->groupBy('service_id')
            ->map(function ($group) {
                $service = $group->first()->service;
                return [
                    'name'          => $service?->title ?? 'Unknown',
                    'booking_count' => $group->count(),
                    'revenue'       => (float) $group->sum(fn ($b) => $b->service?->price ?? 0),
                ];
            })
            ->sortByDesc('booking_count')
            ->take(5)
            ->values();

        return response()->json($data);
    }

    public function bookingsByStatus(Request $request)
    {
        $allSalonIds = $this->ownerSalonIds();
        $salonId     = $request->query('salon_id');
        $salonIds    = ($salonId && $allSalonIds->contains($salonId))
                       ? collect([$salonId])
                       : $allSalonIds;

        $bookings = Booking::query()
            ->whereIn('salon_id', $salonIds)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $statuses = ['pending', 'approved', 'completed', 'rejected'];
        $data = collect($statuses)->map(function ($status) use ($bookings) {
            return [
                'status' => ucfirst($status),
                'count'  => $bookings->firstWhere('status', $status)?->count ?? 0,
            ];
        });

        return response()->json($data);
    }

    public function calendar(Request $request)
    {
        $year    = (int) ($request->query('year', now()->year));
        $month   = (int) ($request->query('month', now()->month));
        $salonId = $request->query('salon_id');
        $salonIds = $this->ownerSalonIds();

        if ($salonId && $salonIds->contains((int) $salonId)) {
            $salonIds = collect([(int) $salonId]);
        }

        $start = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $bookings = Booking::query()
            ->whereIn('salon_id', $salonIds)
            ->whereBetween('date', [$start, $end])
            ->with(['service:id,title', 'salon:id,name'])
            ->orderBy('time')
            ->get();

        $grouped = $bookings
            ->groupBy(fn ($b) => $b->date->format('Y-m-d'))
            ->map(fn ($day) => $day->map(fn ($b) => [
                'id'           => $b->id,
                'name'         => $b->name,
                'email'        => $b->email,
                'phone'        => $b->phone,
                'date'         => $b->date?->format('Y-m-d'),
                'time'         => $b->time,
                'status'       => $b->status,
                'service_name' => $b->service?->title ?? '—',
                'salon_name'   => $b->salon?->name ?? '—',
            ])->values());

        return response()->json([
            'year'     => $year,
            'month'    => $month,
            'bookings' => $grouped,
        ]);
    }

    public function analytics(Request $request)
    {
        $period   = $request->query('period', '30d');
        $salonId  = $request->query('salon_id');
        $salonIds = $this->ownerSalonIds();

        if ($salonId && $salonIds->contains((int) $salonId)) {
            $salonIds = collect([(int) $salonId]);
        }

        $periodStart = match ($period) {
            '7d'  => now()->subDays(7),
            '90d' => now()->subDays(90),
            '12m' => now()->subMonths(12),
            default => now()->subDays(30),
        };

        $allApproved = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->with('service')
            ->get();

        $totalRevenue  = $allApproved->sum(fn ($b) => $b->service?->price ?? 0);
        $todayRevenue  = $allApproved
            ->filter(fn ($b) => $b->date->isToday())
            ->sum(fn ($b) => $b->service?->price ?? 0);
        $periodRevenue = $allApproved
            ->filter(fn ($b) => $b->date->gte($periodStart))
            ->sum(fn ($b) => $b->service?->price ?? 0);

        $periodBookings = Booking::query()
            ->whereIn('salon_id', $salonIds)
            ->where('date', '>=', $periodStart)
            ->get();

        $currentMonth = Booking::query()
            ->whereIn('salon_id', $salonIds)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->count();

        $maxPerMonth = auth()->user()->activeSubscription?->package?->max_bookings_per_month;

        $avgRating = (float) (Salon::whereIn('id', $salonIds)
            ->withAvg('ratings', 'rating')
            ->get()
            ->avg('ratings_avg_rating') ?? 0);

        return response()->json([
            'revenue' => [
                'total'  => (float) $totalRevenue,
                'today'  => (float) $todayRevenue,
                'period' => (float) $periodRevenue,
            ],
            'bookings' => [
                'total'         => $periodBookings->count(),
                'approved'      => $periodBookings->where('status', 'approved')->count(),
                'completed'     => $periodBookings->where('status', 'completed')->count(),
                'pending'       => $periodBookings->where('status', 'pending')->count(),
                'rejected'      => $periodBookings->where('status', 'rejected')->count(),
                'current_month' => $currentMonth,
                'max_per_month' => $maxPerMonth,
            ],
            'avg_rating' => round($avgRating, 1),
        ]);
    }

    public function dailyRevenue(Request $request)
    {
        $period   = $request->query('period', '30d');
        $salonId  = $request->query('salon_id');
        $salonIds = $this->ownerSalonIds();

        if ($salonId && $salonIds->contains((int) $salonId)) {
            $salonIds = collect([(int) $salonId]);
        }

        // 12m → monthly buckets to keep data points manageable
        if ($period === '12m') {
            $bookings = Booking::query()
                ->whereIn('status', ['approved', 'completed'])
                ->whereIn('salon_id', $salonIds)
                ->where('date', '>=', now()->subMonths(12)->startOfMonth())
                ->with('service')
                ->get();

            $data = [];
            for ($i = 11; $i >= 0; $i--) {
                $d   = now()->subMonths($i);
                $key = $d->format('Y-m');
                $data[] = [
                    'date'    => $d->format('M Y'),
                    'revenue' => (float) $bookings
                        ->filter(fn ($b) => $b->date->format('Y-m') === $key)
                        ->sum(fn ($b) => $b->service?->price ?? 0),
                ];
            }
            return response()->json($data);
        }

        $periodStart = match ($period) {
            '7d'  => now()->subDays(7),
            '90d' => now()->subDays(90),
            default => now()->subDays(30),
        };

        $days = (int) $periodStart->diffInDays(now());

        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->where('date', '>=', $periodStart)
            ->with('service')
            ->get();

        $data = [];
        for ($i = $days; $i >= 0; $i--) {
            $d   = now()->subDays($i);
            $key = $d->format('Y-m-d');
            $data[] = [
                'date'    => $d->format('M d'),
                'revenue' => (float) $bookings
                    ->filter(fn ($b) => $b->date->format('Y-m-d') === $key)
                    ->sum(fn ($b) => $b->service?->price ?? 0),
            ];
        }

        return response()->json($data);
    }

    public function monthlyRevenue(Request $request)
    {
        $salonId  = $request->query('salon_id');
        $salonIds = $this->ownerSalonIds();

        if ($salonId && $salonIds->contains((int) $salonId)) {
            $salonIds = collect([(int) $salonId]);
        }

        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->where('date', '>=', now()->subMonths(11)->startOfMonth())
            ->with('service')
            ->get();

        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $d   = now()->subMonths($i);
            $key = $d->format('Y-m');
            $data[] = [
                'month'   => $d->format('M Y'),
                'revenue' => (float) $bookings
                    ->filter(fn ($b) => $b->date->format('Y-m') === $key)
                    ->sum(fn ($b) => $b->service?->price ?? 0),
            ];
        }

        return response()->json($data);
    }

    public function profitBreakdown(Request $request)
    {
        $salonId  = $request->query('salon_id');
        $salonIds = $this->ownerSalonIds();

        if ($salonId && $salonIds->contains((int) $salonId)) {
            $salonIds = collect([(int) $salonId]);
        }

        $revenueBookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->with('service')
            ->get();

        $totalRevenue = (float) $revenueBookings->sum(fn ($b) => $b->service?->price ?? 0);
        $confirmedBookings = $revenueBookings->count();
        $averageBookingValue = $confirmedBookings > 0
            ? (float) ($totalRevenue / $confirmedBookings)
            : 0.0;

        $topServiceGroup = $revenueBookings
            ->groupBy('service_id')
            ->sortByDesc(fn ($group) => $group->sum(fn ($booking) => $booking->service?->price ?? 0))
            ->first();

        $topService = $topServiceGroup ? [
            'name' => $topServiceGroup->first()?->service?->title ?? 'Unknown',
            'revenue' => (float) $topServiceGroup->sum(fn ($booking) => $booking->service?->price ?? 0),
            'bookings' => $topServiceGroup->count(),
        ] : null;

        return response()->json([
            'total_revenue' => $totalRevenue,
            'confirmed_bookings' => $confirmedBookings,
            'average_booking_value' => $averageBookingValue,
            'top_service' => $topService,
        ]);
    }

    public function profitByMonth(Request $request)
    {
        $year     = (int) ($request->query('year', now()->year));
        $month    = (int) ($request->query('month', now()->month));
        $salonId  = $request->query('salon_id');
        $salonIds = $this->ownerSalonIds();

        if ($salonId && $salonIds->contains((int) $salonId)) {
            $salonIds = collect([(int) $salonId]);
        }

        $bookings = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->whereIn('salon_id', $salonIds)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->with(['service', 'salon'])
            ->get();

        $totalRevenue = (float) $bookings->sum(fn ($b) => $b->service?->price ?? 0);

        $bySalon = $bookings
            ->groupBy('salon_id')
            ->map(function ($group) {
                return [
                    'name'     => $group->first()->salon?->name ?? 'Unknown',
                    'revenue'  => (float) $group->sum(fn ($b) => $b->service?->price ?? 0),
                    'bookings' => $group->count(),
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        return response()->json([
            'year'          => $year,
            'month'         => $month,
            'total_revenue' => $totalRevenue,
            'by_salon'      => $bySalon,
        ]);
    }

    public function salons()
    {
        $salons = Salon::where('created_by', auth()->id())
            ->select('id', 'name')
            ->get();

        return response()->json($salons);
    }
}
