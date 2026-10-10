<?php

namespace App\Filament\Admin\Resources\ImageCycles\Pages;

use App\Filament\Admin\Resources\ImageCycles\ImageCycleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListImageCycles extends ListRecords
{
    protected static string $resource = ImageCycleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
