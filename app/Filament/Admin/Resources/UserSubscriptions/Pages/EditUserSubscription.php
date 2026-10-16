<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Pages;

use App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource;
use App\Filament\Concerns\HandlesServiceValidation;
use App\Services\SubscriptionService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUserSubscription extends EditRecord
{
    use HandlesServiceValidation;

    protected static string $resource = UserSubscriptionResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless(auth()->user()->hasRole('admin'), 403);

        return $this->withFormValidation(fn () => app(SubscriptionService::class)->updatePayment($record, auth()->user(), $data));
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
