<?php

namespace App\Support;

use App\Models\Booking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

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
        $values = $query->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()->groupBy(fn ($booking) => $booking->date->format($format))
            ->map(fn ($group) => $group->sum(fn ($booking) => $booking->service?->price ?? 0));
        $labels = $data = [];
        for ($date = $start->copy(); $date->lte($end); $monthly ? $date->addMonthNoOverflow() : $date->addDay()) {
            $labels[] = $date->format($monthly ? 'M Y' : 'M d');
            $data[] = (float) ($values[$date->format($format)] ?? 0);
        }

        return compact('labels', 'data');
    }
}
