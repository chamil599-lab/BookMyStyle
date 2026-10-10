<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Pages;

use App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserSubscription extends CreateRecord
{
    use \App\Filament\Concerns\HandlesServiceValidation;

    protected static string $resource = UserSubscriptionResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        return $this->withFormValidation(fn () => app(\App\Services\SubscriptionService::class)->request(\App\Models\User::findOrFail($data['user_id']), \App\Models\SubscriptionPackage::findOrFail($data['package_id']), $data, auth()->user()));
    }
}
