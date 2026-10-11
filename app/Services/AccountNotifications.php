<?php

namespace App\Services;

use App\Models\User;
use App\Support\ManagementAccess;
use Illuminate\Support\Facades\RateLimiter;

class AccountNotifications
{
    public function canAccess(?User $user): bool
    {
        return $user && $user->hasVerifiedEmail() && ($user->hasRole('customer')
            || ManagementAccess::admin($user) || ManagementAccess::owner($user));
    }

    public function actor(?User $user): User
    {
        $user = $user?->fresh();
        abort_unless($this->canAccess($user), 403);

        return $user;
    }

    public function query(User $user)
    {
        return $this->actor($user)->notifications()->orderByDesc('created_at')->orderByDesc('id');
    }

    public function unread(User $user): int
    {
        return $this->actor($user)->unreadNotifications()->count();
    }

    public function inboxUrl(User $user): string
    {
        return $user->hasRole('customer') ? route('customer.notifications') : route('filament.admin.pages.notification-inbox');
    }

    public function read(User $user, ?string $id = null): void
    {
        $this->limit($user);
        $query = $this->query($user);
        if ($id !== null) {
            $query->whereKey($id)->firstOrFail()->markAsRead();
        } else {
            $query->whereNull('read_at')->update(['read_at' => now()]);
        }
    }

    public function delete(User $user, ?string $id = null): void
    {
        $this->limit($user);
        $query = $this->query($user);
        if ($id !== null) {
            $query->whereKey($id)->firstOrFail()->delete();
        } else {
            $query->delete();
        }
    }

    private function limit(User $user): void
    {
        $user = $this->actor($user);
        $key = 'notification-actions:'.$user->id;
        abort_if(RateLimiter::tooManyAttempts($key, 40), 429);
        RateLimiter::hit($key, 60);
    }

    public function destination(User $user, array $data): string
    {
        // Stored/imported URLs never supply an external or executable destination.
        if ($user->hasRole('customer')) {
            return route(($data['status'] ?? null) === 'approved' ? 'customer.upcoming' : 'customer.history');
        }

        return isset($data['subscription_id'])
            ? route('filament.admin.resources.user-subscriptions.index')
            : route('filament.admin.resources.bookings.index');
    }
}
