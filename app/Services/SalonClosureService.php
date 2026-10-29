<?php

namespace App\Services;

use App\Models\Salon;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SalonClosureService
{
    public function save(User $actor, Salon $salon, array $data): void
    {
        $data = Validator::make($data, [
            'dates' => 'present|array|max:730',
            'dates.*' => 'required|date_format:Y-m-d|distinct|after_or_equal:today',
            'reason' => 'nullable|string|max:255',
        ])->validate();

        DB::transaction(function () use ($actor, $salon, $data) {
            // Match booking lock order so a closure and a new appointment cannot race.
            User::whereKey($salon->created_by)->lockForUpdate()->firstOrFail();
            $locked = Salon::whereKey($salon->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $locked);
            abort_unless((int) $locked->created_by === (int) $salon->created_by, 409);
            $conflicts = $locked->bookings()->whereIn('date', $data['dates'])
                ->whereIn('status', ['pending', 'approved'])->orderBy('date')->get();
            if ($conflicts->isNotEmpty()) {
                $appointments = $conflicts->map(fn ($booking) => '#'.$booking->id.' '.$booking->date->format('Y-m-d').' '.$booking->time.' ('.$booking->name.')')->implode(', ');
                throw ValidationException::withMessages(['dates' => 'Reschedule or cancel these appointments before closing: '.$appointments]);
            }
            $locked->closedDates()->where('date', '>=', today()->format('Y-m-d'))
                ->whereNotIn('date', $data['dates'])->delete();
            foreach ($data['dates'] as $date) {
                // Keep reasons on existing closures; the supplied reason applies to newly closed dates.
                $locked->closedDates()->firstOrCreate(['date' => $date], ['reason' => $data['reason'] ?? null]);
            }
        }, 3);
    }
}
