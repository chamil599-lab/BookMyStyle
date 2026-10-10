<?php

namespace App\Filament\Admin\Resources\SubscriptionPackages\Pages;

use App\Filament\Admin\Resources\SubscriptionPackages\SubscriptionPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSubscriptionPackage extends EditRecord
{
    protected static string $resource = SubscriptionPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()->using(fn ($record) => app(\App\Services\PackageManagement::class)->archive(auth()->user(), $record)),
            RestoreAction::make(),
        ];
    }
}
