<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    protected static string $resource = BookingResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Illuminate\Support\Facades\Gate::authorize('update', $this->getRecord());
        if (! auth()->user()->hasRole('admin')) {
            abort_unless(\App\Models\Salon::whereKey($data['salon_id'] ?? null)
                ->where('created_by', auth()->id())->exists(), 403);
            abort_unless(\App\Models\Service::whereKey($data['service_id'] ?? null)
                ->where('created_by', auth()->id())->exists(), 403);
        }

        return $data;
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
