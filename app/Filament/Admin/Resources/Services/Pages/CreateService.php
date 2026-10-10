<?php

namespace App\Filament\Admin\Resources\Services\Pages;

use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Models\Salon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateService extends CreateRecord
{
    protected static string $resource = ServiceResource::class;

    protected function beforeCreate(): void
    {
        // Get the current user
        $user = Auth::user();
        
        // Check if user has active subscription
        if (!$user->hasActiveSubscription()) {
            Notification::make()
                ->danger()
                ->title('No Active Subscription')
                ->body('You need an active subscription to add services.')
                ->send();
            throw new \Exception('No active subscription');
        }

        // Get the salon from form data
        $salonId = $this->form->getRawState()['salon_id'] ?? null;
        if (!$salonId) {
            Notification::make()
                ->danger()
                ->title('Salon Required')
                ->body('Please select a salon.')
                ->send();
            throw new \Exception('Salon not selected');
        }

        $salon = Salon::where('created_by', $user->id)->find($salonId);
        if (!$salon) {
            Notification::make()
                ->danger()
                ->title('Invalid Salon')
                ->body('The selected salon does not exist.')
                ->send();
            throw new \Exception('Salon not found');
        }

        // Check if user can add more services to this salon
        if (!$user->canAddMoreServices($salon)) {
            $package = $user->activeSubscription->package;
            Notification::make()
                ->danger()
                ->title('Service Limit Reached')
                ->body("Your {$package->name} plan allows maximum {$package->max_services} services. Current count: {$salon->services()->count()}")
                ->send();
            throw new \Exception('Service limit exceeded');
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['salon_id']);

        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
