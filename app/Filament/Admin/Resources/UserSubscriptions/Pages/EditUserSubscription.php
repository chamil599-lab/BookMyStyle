<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Pages;

use App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUserSubscription extends EditRecord
{
    use \App\Filament\Concerns\HandlesServiceValidation;

    protected static string $resource = UserSubscriptionResource::class;

    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        return $this->withFormValidation(fn () => app(\App\Services\SubscriptionService::class)->updatePayment($record, auth()->user(), $data));
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
