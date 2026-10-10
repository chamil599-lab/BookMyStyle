<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Console\Command;

class SendSubscriptionExpiryReminders extends Command
{
    protected $signature = 'subscriptions:notify-expiring';

    protected $description = 'Send email reminders to users whose subscriptions expire in 3 days';

    public function handle(): int
    {
        $subscriptions = UserSubscription::query()
            ->where('status', 'active')
            ->whereDate('ends_at', now()->addDays(3)->toDateString())
            ->with(['user', 'package'])
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            if ($subscription->user) {
                $subscription->user->notify(new SubscriptionExpiringNotification($subscription));
                $count++;
            }
        }

        $this->info("Sent {$count} subscription expiry reminder(s).");

        return self::SUCCESS;
    }
}
