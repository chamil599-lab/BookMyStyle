<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    use \App\Filament\Concerns\HandlesServiceValidation;

    protected static string $resource = BookingResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return $this->withFormValidation(fn () => app(\App\Services\BookingService::class)->create($data, auth()->user(), true));
    }

    protected function beforeCreate(): void
    {
        \Illuminate\Support\Facades\Gate::authorize('create', \App\Models\Booking::class);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()->hasRole('admin')) {
            return $data;
        }
        $ownerSalons = \App\Models\Salon::where('created_by', auth()->id())->get();

        // If owner has only 1 salon, auto-fill salon_id
        if ($ownerSalons->count() === 1) {
            $data['salon_id'] = $ownerSalons->first()->id;
        }

        abort_unless($ownerSalons->contains('id', (int) ($data['salon_id'] ?? 0)), 403);
        abort_unless(\App\Models\Service::whereKey($data['service_id'] ?? null)
            ->where('created_by', auth()->id())->exists(), 403);

        return $data;
    }
}
