<?php

namespace App\Filament\Admin\Resources\DealsPages\Pages;

use App\Filament\Admin\Resources\DealsPages\DealsPageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDealsPage extends ViewRecord
{
    protected static string $resource = DealsPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
