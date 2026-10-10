<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\SiteVisit;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        $subscriptionRevenue = UserSubscription::where('status', 'active')->sum('amount_paid');
        $approvedBookings = Booking::query()->where('status', 'approved');

        // Single aggregate join replaces two N+1 load-all + PHP sums.
        $todayStart = now()->startOfDay();
        $bookingRevRow = Booking::query()
            ->leftJoin('services', 'bookings.service_id', '=', 'services.id')
            ->whereIn('bookings.status', ['approved', 'completed'])
            ->selectRaw("
                COALESCE(SUM(services.price), 0) AS total_revenue,
                COALESCE(SUM(CASE WHEN bookings.created_at >= ? THEN services.price ELSE 0 END), 0) AS today_revenue
            ", [$todayStart])
            ->first();

        $bookingRevenue       = (float) ($bookingRevRow->total_revenue ?? 0);
        $todayBookingRevenue  = (float) ($bookingRevRow->today_revenue ?? 0);

        return response()->json([
            'subscriptions' => [
                'total'    => UserSubscription::count(),
                'active'   => UserSubscription::where('status', 'active')->where('ends_at', '>', now())->count(),
                'pending'  => UserSubscription::where('status', 'pending')->count(),
                'expired'  => UserSubscription::where(function ($q) {
                    $q->where('status', 'expired')->orWhere('ends_at', '<=', now());
                })->where('status', '!=', 'canceled')->count(),
                'canceled' => UserSubscription::where('status', 'canceled')->count(),
            ],
            'revenue' => [
                'subscription_total' => (float) $subscriptionRevenue,
                'booking_total'      => (float) $bookingRevenue,
                'today_bookings'     => (float) $todayBookingRevenue,
            ],
            'bookings' => [
                'total'      => (clone $approvedBookings)->count(),
                'this_month' => (clone $approvedBookings)
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count(),
            ],
            'users' => [
                'total_owners'      => User::role('owner')->count(),
                'pending_approval'  => User::role('owner')->where('is_approved', false)->count(),
            ],
            'visits' => [
                'total' => SiteVisit::count(),
                'today' => SiteVisit::whereDate('occurred_at', today())->count(),
                'this_month' => SiteVisit::whereYear('occurred_at', now()->year)
                    ->whereMonth('occurred_at', now()->month)
                    ->count(),
                'unique_visitors' => SiteVisit::whereNotNull('visitor_id_hash')->distinct('visitor_id_hash')->count('visitor_id_hash'),
            ],
        ]);
    }

    public function revenueChart(Request $request)
    {
        $year = (int) ($request->query('year', now()->year));

        // SQL GROUP BY replaces fetch-all + 12× PHP filter passes.
        $rows = Booking::query()
            ->leftJoin('services', 'bookings.service_id', '=', 'services.id')
            ->whereIn('bookings.status', ['approved', 'completed'])
            ->whereYear('bookings.date', $year)
            ->selectRaw('MONTH(bookings.date) AS m, COALESCE(SUM(services.price), 0) AS revenue')
            ->groupBy('m')
            ->pluck('revenue', 'm');

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[] = [
                'month'   => now()->setDate($year, $m, 1)->format('M'),
                'revenue' => (float) ($rows[$m] ?? 0),
            ];
        }

        return response()->json($data);
    }

    public function visitsChart(Request $request)
    {
        $year = (int) ($request->query('year', now()->year));

        $rows = SiteVisit::query()
            ->whereYear('occurred_at', $year)
            ->selectRaw('MONTH(occurred_at) AS m, COUNT(*) AS visits')
            ->groupBy('m')
            ->pluck('visits', 'm');

        $data = [];
        for ($m = 1; $m <= 12; $m++) {
            $data[] = [
                'month'  => now()->setDate($year, $m, 1)->format('M'),
                'visits' => (int) ($rows[$m] ?? 0),
            ];
        }

        return response()->json($data);
    }

    public function subscriptionChart()
    {
        $active   = UserSubscription::where('status', 'active')->where('ends_at', '>', now())->count();
        $pending  = UserSubscription::where('status', 'pending')->count();
        $expired  = UserSubscription::where(function ($q) {
            $q->where('status', 'expired')->orWhere('ends_at', '<=', now());
        })->where('status', '!=', 'canceled')->count();
        $canceled = UserSubscription::where('status', 'canceled')->count();

        return response()->json([
            ['status' => 'Active',   'count' => $active],
            ['status' => 'Pending',  'count' => $pending],
            ['status' => 'Expired',  'count' => $expired],
            ['status' => 'Canceled', 'count' => $canceled],
        ]);
    }

    public function topPackages()
    {
        $data = UserSubscription::query()
            ->where('status', 'active')
            ->with('package')
            ->get()
            ->groupBy('package_id')
            ->map(function ($group) {
                $package = $group->first()->package;
                return [
                    'name'             => $package?->name ?? 'Unknown',
                    'subscriber_count' => $group->count(),
                    'revenue'          => (float) $group->sum('amount_paid'),
                ];
            })
            ->sortByDesc('subscriber_count')
            ->take(5)
            ->values();

        return response()->json($data);
    }
}
