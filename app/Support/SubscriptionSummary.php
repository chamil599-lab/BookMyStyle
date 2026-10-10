<?php

namespace App\Support;

use App\Models\UserSubscription;

class SubscriptionSummary
{
    public function counts(): array
    {
        $counts = array_fill_keys(['active', 'scheduled', 'pending', 'expired', 'canceled', 'rejected'], 0);
        $records = UserSubscription::select('status', 'starts_at', 'ends_at')->get();
        foreach ($records as $record) {
            $status = $record->status;
            if ($status === 'active') {
                $status = ! $record->ends_at || $record->ends_at <= now() ? 'expired'
                    : ($record->starts_at && $record->starts_at > now() ? 'scheduled' : 'active');
            }
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return ['total' => $records->count()] + $counts;
    }
}
