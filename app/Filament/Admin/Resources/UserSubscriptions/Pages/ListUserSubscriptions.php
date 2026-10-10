<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Pages;

use App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource;
use App\Models\UserSubscription;
use App\RolesEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListUserSubscriptions extends ListRecords
{
    protected static string $resource = UserSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        $isAdmin = $user?->hasRole(RolesEnum::ADMIN->value) ?? false;

        if ($isAdmin) {
            return [
                Action::make('generate')
                    ->label('Generate Subscriptions')
                    ->icon('heroicon-m-sparkles')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Subscriptions')
                    ->modalDescription('This will approve all pending subscriptions and activate them. Each owner\'s selected package will be used.')
                    ->modalSubmitActionLabel('Generate')
                    ->action(function () {
                        $pending = UserSubscription::where('status', 'pending')->get();

                        if ($pending->isEmpty()) {
                            Notification::make()
                                ->title('No pending subscriptions found')
                                ->warning()
                                ->send();
                            return;
                        }

                        $activated = 0;
                        foreach ($pending as $subscription) {
                            $package = $subscription->package;

                            // If this is a renewal, start after the parent subscription ends
                            if ($subscription->renewed_from) {
                                $parent = $subscription->renewedFrom;
                                $startsAt = $parent && $parent->ends_at && $parent->ends_at->isFuture()
                                    ? $parent->ends_at
                                    : now();
                            } else {
                                $startsAt = now();
                            }

                            $subscription->update([
                                'status' => 'active',
                                'amount_paid' => $package->price,
                                'starts_at' => $startsAt,
                                'ends_at' => $startsAt->copy()->addDays($package->duration_days),
                            ]);
                            $activated++;
                        }

                        Notification::make()
                            ->title('Subscriptions Generated')
                            ->body("Activated {$activated} subscription(s).")
                            ->success()
                            ->send();
                    }),
                CreateAction::make(),
            ];
        }

        // Owner: show "Choose a Package" or "Change Plan" action
        $hasSubscription = $user->hasActiveOrPendingSubscription();

        return [
            Action::make('choose_package')
                ->label($hasSubscription ? 'Change Plan' : 'Choose a Package')
                ->icon($hasSubscription ? 'heroicon-m-arrow-path' : 'heroicon-m-plus')
                ->color($hasSubscription ? 'warning' : 'primary')
                ->action(fn () => redirect()->route('subscription.required')),
        ];
    }
}
