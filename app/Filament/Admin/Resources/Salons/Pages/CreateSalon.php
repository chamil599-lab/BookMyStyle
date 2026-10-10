<?php

namespace App\Filament\Admin\Resources\Salons\Pages;

use App\Filament\Admin\Resources\Salons\SalonResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class CreateSalon extends CreateRecord
{
    protected static string $resource = SalonResource::class;

    protected function beforeCreate(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Check if user has active subscription
        if (!$user->hasActiveSubscription()) {
            Notification::make()
                ->title('No Active Subscription')
                ->body('You need an active subscription to create salons.')
                ->danger()
                ->send();

            throw new \Exception('No active subscription');
        }

        $package = $user->activeSubscription->package;
        $currentSalonCount = $user->salons()->count();

        // Check if user has reached salon limit
        if ($currentSalonCount >= $package->max_salons) {
            Notification::make()
                ->title('Salon Limit Reached')
                ->body("Your {$package->name} plan allows a maximum of {$package->max_salons} salons. You currently have {$currentSalonCount}.")
                ->danger()
                ->send();

            throw new \Exception('Salon limit exceeded');
        }

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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $data['created_by'] = $user->id;
        $data['updated_by'] = $user->id;
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index'); // Redirect to salon list
    }
}
