<?php

namespace App\Support;

use App\Models\UserSubscription;

class SubscriptionSummary
{
    public function counts(): array
    {
        $counts = array_fill_keys(['active', 'scheduled', 'pending', 'expired', 'canceled', 'rejected'], 0);
        $now = now();
        $records = UserSubscription::query()->toBase()
            ->selectRaw("CASE WHEN status = 'active' THEN CASE
                WHEN ends_at IS NULL OR ends_at <= ? THEN 'expired'
                WHEN starts_at > ? THEN 'scheduled'
                ELSE 'active' END ELSE status END as summary_status, COUNT(*) as total", [$now, $now])
            ->groupBy('summary_status')->get();
        foreach ($records as $record) {
            $counts[$record->summary_status] = (int) $record->total;
        }

        return ['total' => array_sum($counts)] + $counts;
    }
}
