<?php

namespace App\Filament\Admin\Resources\FeaturedSalons\Pages;

use App\Filament\Admin\Resources\FeaturedSalons\FeaturedSalonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFeaturedSalon extends CreateRecord
{
    protected static string $resource = FeaturedSalonResource::class;
}
