<?php

namespace App\Notifications;

use App\Models\UserSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public UserSubscription $subscription
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        $days = $this->subscription->daysRemaining();
        $packageName = $this->subscription->package?->name ?? 'your plan';
        $expiryDate = $this->subscription->ends_at->format('M d, Y');

        return [
            'subscription_id' => $this->subscription->id,
            'days_remaining' => $days,
            'package_name' => $packageName,
            'expires_at' => $expiryDate,
            'title' => 'Subscription expiring',
            'message' => "Your {$packageName} subscription expires in {$days} days ({$expiryDate}).",
            'link' => '/owner/subscription',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $days = $this->subscription->daysRemaining();
        $packageName = $this->subscription->package?->name ?? 'your plan';
        $expiryDate = $this->subscription->ends_at->format('M d, Y');

        return (new MailMessage)
            ->subject("Your subscription expires in {$days} days")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your **{$packageName}** subscription will expire on **{$expiryDate}** ({$days} days from now).")
            ->line('To continue enjoying uninterrupted access to your salon management features, please renew your subscription before it expires.')
            ->action('Renew Subscription', url('/owner/subscription'))
            ->line('If you have any questions, feel free to contact our support team.');
    }
}
