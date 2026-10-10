<?php

namespace App\Filament\Admin\Resources\ServicePages\Pages;

use App\Filament\Admin\Resources\ServicePages\ServicePageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateServicePage extends CreateRecord
{
    protected static string $resource = ServicePageResource::class;
}
