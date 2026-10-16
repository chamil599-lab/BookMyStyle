<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use App\Filament\Concerns\HandlesServiceValidation;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Service;
use App\Services\BookingService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class CreateBooking extends CreateRecord
{
    use HandlesServiceValidation;

    protected static string $resource = BookingResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->withFormValidation(fn () => app(BookingService::class)->create($data, auth()->user(), true));
    }

    protected function beforeCreate(): void
    {
        Gate::authorize('create', Booking::class);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->user()->hasRole('admin')) {
            return $data;
        }
        $ownerSalons = Salon::where('created_by', auth()->id())->get();

        // If owner has only 1 salon, auto-fill salon_id
        if ($ownerSalons->count() === 1) {
            $data['salon_id'] = $ownerSalons->first()->id;
        }

        abort_unless($ownerSalons->contains('id', (int) ($data['salon_id'] ?? 0)), 403);
        abort_unless(Service::whereKey($data['service_id'] ?? null)
            ->where('created_by', auth()->id())->exists(), 403);

        return $data;
    }
}
