<?php

namespace App\Filament\Admin\Resources\ServicePages\Pages;

use App\Filament\Admin\Resources\ServicePages\ServicePageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewServicePage extends ViewRecord
{
    protected static string $resource = ServicePageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
