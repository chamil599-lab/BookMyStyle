<?php

namespace App\Filament\Admin\Resources\Services\Pages;

use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Filament\Concerns\HandlesServiceValidation;
use App\Services\CatalogueManagement;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateService extends CreateRecord
{
    use HandlesServiceValidation;

    protected static string $resource = ServiceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->withFormValidation(fn () => app(CatalogueManagement::class)->createService(auth()->user(), $data));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
