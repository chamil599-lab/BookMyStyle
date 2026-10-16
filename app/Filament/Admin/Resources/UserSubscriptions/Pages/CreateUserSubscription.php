<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Pages;

use App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource;
use App\Filament\Concerns\HandlesServiceValidation;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\SubscriptionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUserSubscription extends CreateRecord
{
    use HandlesServiceValidation;

    protected static string $resource = UserSubscriptionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        return $this->withFormValidation(fn () => app(SubscriptionService::class)->request(User::findOrFail($data['user_id']), SubscriptionPackage::findOrFail($data['package_id']), $data, auth()->user()));
    }
}
