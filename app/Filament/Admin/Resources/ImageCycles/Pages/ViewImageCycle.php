<?php

namespace App\Filament\Admin\Resources\ImageCycles\Pages;

use App\Filament\Admin\Resources\ImageCycles\ImageCycleResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewImageCycle extends ViewRecord
{
    protected static string $resource = ImageCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
