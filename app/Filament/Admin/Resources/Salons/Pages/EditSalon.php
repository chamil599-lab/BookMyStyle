<?php

namespace App\Filament\Admin\Resources\Salons\Pages;

use App\Filament\Admin\Resources\Salons\SalonResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditSalon extends EditRecord
{
    protected static string $resource = SalonResource::class;

    protected function beforeSave(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user has active subscription
        if (!$user->hasActiveSubscription()) {
            Notification::make()
                ->title('No Active Subscription')
                ->body('You need an active subscription to edit salons.')
                ->danger()
                ->send();

            throw new \Exception('No active subscription');
        }

        $package = $user->activeSubscription->package;

        // Check if number_of_staff doesn't exceed max_staff from subscription
        $numberOfStaff = $this->form->getState()['number_of_staff'] ?? 0;
        if ($numberOfStaff > $package->max_staff) {
            Notification::make()
                ->title('Staff Limit Exceeded')
                ->body("Your {$package->name} plan allows a maximum of {$package->max_staff} staff members per salon. You entered {$numberOfStaff}.")
                ->danger()
                ->send();

            throw new \Exception('Staff limit exceeded for this salon');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Redirect to salon list
    }
}
