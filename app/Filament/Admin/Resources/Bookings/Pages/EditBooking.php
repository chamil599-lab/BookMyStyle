<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Concerns\HandlesServiceValidation;
use App\Models\Salon;
use App\Models\Service;
use App\Services\BookingService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class EditBooking extends EditRecord
{
    use HandlesServiceValidation;

    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        Gate::authorize('update', $this->getRecord());
        if (! auth()->user()->hasRole('admin')) {
            abort_unless(Salon::whereKey($data['salon_id'] ?? null)
                ->where('created_by', auth()->id())->exists(), 403);
            abort_unless(Service::whereKey($data['service_id'] ?? null)
                ->where('created_by', auth()->id())->exists(), 403);
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->withFormValidation(fn () => app(BookingService::class)->update($record, $data, auth()->user()));
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
