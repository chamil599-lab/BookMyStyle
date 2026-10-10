<?php

namespace App\Filament\Admin\Resources\DealsPages\Pages;

use App\Filament\Admin\Resources\DealsPages\DealsPageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDealsPages extends ListRecords
{
    protected static string $resource = DealsPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
