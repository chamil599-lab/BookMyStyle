<?php

namespace App\Filament\Admin\Resources\SubscriptionPackages\Pages;

use App\Filament\Admin\Resources\SubscriptionPackages\SubscriptionPackageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSubscriptionPackage extends ViewRecord
{
    protected static string $resource = SubscriptionPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
