<?php

namespace App\Filament\Admin\Resources\Salons\Pages;

use App\Filament\Admin\Resources\Salons\SalonResource;
use App\Filament\Concerns\HandlesServiceValidation;
use App\Services\CatalogueManagement;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalon extends CreateRecord
{
    use HandlesServiceValidation;

    protected static string $resource = SalonResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->withFormValidation(fn () => app(CatalogueManagement::class)->createSalon(auth()->user(), $data));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
