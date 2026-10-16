<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Pages;

use App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource;
use App\Models\User;
use App\RolesEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\ListRecords;

class ListUserSubscriptions extends ListRecords
{
    protected static string $resource = UserSubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();
        $isAdmin = $user?->hasRole(RolesEnum::ADMIN->value) ?? false;

        if ($isAdmin) {
            return [
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
