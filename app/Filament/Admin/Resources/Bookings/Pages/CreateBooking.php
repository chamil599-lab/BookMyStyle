<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;

    protected function beforeCreate(): void
    {
        \Illuminate\Support\Facades\Gate::authorize('create', \App\Models\Booking::class);
        $user = Auth::user();

        if ($user->hasRole('admin')) {
            return;
        }

        // Check if user has active subscription
        if (! $user->hasActiveSubscription()) {
            Notification::make()
                ->danger()
                ->title('No Active Subscription')
                ->body('You need an active subscription to create bookings.')
                ->send();
            throw new \Exception('No active subscription');
        }

        // Check if user can create more bookings this month
        if (! $user->canCreateMoreBookings()) {
            $package = $user->activeSubscription->package;
            $currentCount = $user->getMonthlyBookingsCount();

            Notification::make()
                ->danger()
                ->title('Monthly Booking Limit Reached')
                ->body("Your {$package->name} plan allows {$package->max_bookings_per_month} bookings per month. Current count: {$currentCount}. Limit resets on ".now()->addMonth()->format('M d, Y'))
                ->send();
            throw new \Exception('Monthly booking limit exceeded');
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()->hasRole('admin')) {
            return $data;
        }
        $ownerSalons = \App\Models\Salon::where('created_by', auth()->id())->get();

        // If owner has only 1 salon, auto-fill salon_id
        if ($ownerSalons->count() === 1) {
            $data['salon_id'] = $ownerSalons->first()->id;
        }

        abort_unless($ownerSalons->contains('id', (int) ($data['salon_id'] ?? 0)), 403);
        abort_unless(\App\Models\Service::whereKey($data['service_id'] ?? null)
            ->where('created_by', auth()->id())->exists(), 403);

        return $data;
    }
}
