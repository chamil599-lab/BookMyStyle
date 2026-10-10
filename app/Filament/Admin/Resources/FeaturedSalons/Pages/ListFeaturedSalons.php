<?php

namespace App\Filament\Admin\Resources\FeaturedSalons\Pages;

use App\Filament\Admin\Resources\FeaturedSalons\FeaturedSalonResource;
use Filament\Resources\Pages\ListRecords;

class ListFeaturedSalons extends ListRecords
{
    protected static string $resource = FeaturedSalonResource::class;
}
