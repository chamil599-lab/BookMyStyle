<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Current-price estimates for confirmed appointments, not a payment ledger. */
class BookingValue
{
    public static function query(?iterable $salonIds = null): Builder
    {
        return Booking::query()->whereIn('status', ['approved', 'completed'])
            ->when($salonIds !== null, fn ($q) => $q->whereIn('salon_id', $salonIds))
            ->with(['service' => fn ($q) => $q->withTrashed()]);
    }

    public static function chart(Builder $query, Carbon $start, Carbon $end, bool $monthly = false): array
    {
        $format = $monthly ? 'Y-m' : 'Y-m-d';
        $period = $monthly ? 'SUBSTR(bookings.date, 1, 7)' : 'bookings.date';
        $values = (clone $query)->reorder()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->toBase()
            ->selectRaw("{$period} as period, SUM((SELECT services.price FROM services WHERE services.id = bookings.service_id)) as value")
            ->groupByRaw($period)
            ->pluck('value', 'period');
        $labels = $data = [];
        for ($date = $start->copy(); $date->lte($end); $monthly ? $date->addMonthNoOverflow() : $date->addDay()) {
            $labels[] = $date->format($monthly ? 'M Y' : 'M d');
            $data[] = (float) ($values[$date->format($format)] ?? 0);
        }

        return compact('labels', 'data');
    }

    public static function total(Builder $query): float
    {
        // Include archived services, just as the reporting relationship does.
        return (float) (clone $query)->toBase()->sum(DB::raw(
            '(SELECT services.price FROM services WHERE services.id = bookings.service_id)'
        ));
    }
}
