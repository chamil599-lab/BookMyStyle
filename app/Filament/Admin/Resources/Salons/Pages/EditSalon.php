<?php

namespace App\Filament\Admin\Resources\Salons\Pages;

use App\Filament\Admin\Resources\Salons\SalonResource;
use App\Services\CatalogueManagement;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSalon extends EditRecord
{
    use \App\Filament\Concerns\HandlesServiceValidation;

    protected static string $resource = SalonResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $updated = $this->withFormValidation(fn () => app(CatalogueManagement::class)->updateSalon(auth()->user(), $record, $data));
        $record->setRawAttributes($updated->getAttributes(), true);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
