<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Notifications\SubscriptionExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendSubscriptionExpiryReminders extends Command
{
    protected $signature = 'subscriptions:notify-expiring {--send : Deliver reminders after host operations are enabled}';

    protected $description = 'Send email reminders to users whose subscriptions expire in 3 days';

    public function handle(): int
    {
        $subscriptions = UserSubscription::query()->active()
            ->where('ends_at', '>=', now()->addDays(3)->startOfDay())
            ->where('ends_at', '<', now()->addDays(4)->startOfDay())
            ->whereHas('user', fn ($query) => $query->where('is_approved', true)->whereNotNull('email_verified_at')
                ->whereHas('roles', fn ($roles) => $roles->where('name', 'owner')))
            ->with(['user', 'package'])
            ->lazyById();

        if ($this->option('send') && ! config('operations.subscription_reminders_enabled')) {
            $this->error('Subscription reminder delivery is disabled in host operations configuration.');

            return self::FAILURE;
        }

        $count = 0;

        foreach ($subscriptions as $subscription) {
            $renewed = $subscription->user->subscriptions()->where('status', 'active')
                ->where('ends_at', '>', $subscription->ends_at)
                ->where(fn ($query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', $subscription->ends_at))
                ->exists();
            if ($renewed) {
                continue;
            }
            if (! $this->option('send')) {
                $count++;

                continue;
            }
            $key = 'subscription-expiry:'.$subscription->id.':'.$subscription->ends_at->getTimestamp();
            $lock = Cache::lock($key.':lock', 300);
            if (! $lock->get()) {
                continue;
            }
            try {
                if (! Cache::has($key)) {
                    $subscription->user->notify(new SubscriptionExpiringNotification($subscription));
                    Cache::put($key, true, now()->addDays(7));
                    $count++;
                }
            } finally {
                $lock->release();
            }
        }

        $this->info($this->option('send') ? "Sent {$count} subscription expiry reminder(s)." : "Dry run: {$count} eligible reminder(s); no notifications sent.");

        return self::SUCCESS;
    }
}
