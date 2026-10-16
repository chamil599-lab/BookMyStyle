<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingStatusChangedNotification;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function create(array $data, ?User $actor = null, bool $management = false): Booking
    {
        $data = $this->validate($data);
        $ownerId = (int) Salon::findOrFail($data['salon_id'])->created_by;

        return DB::transaction(function () use ($data, $actor, $management, $ownerId) {
            $salon = $this->lockSalon((int) $data['salon_id'], $ownerId);
            $owner = User::whereKey($salon->created_by)->lockForUpdate()->firstOrFail();
            if ($management) {
                $actor = $actor?->fresh();
                abort_unless($actor !== null, 403);
                Gate::forUser($actor)->authorize('create', Booking::class);
                abort_unless($actor->hasRole('admin') || (int) $salon->created_by === (int) $actor->id, 403);
            }
            if (! $management || ! $actor->hasRole('admin')) {
                $subscription = $owner->subscriptions()->active()->lockForUpdate()->first();
                $package = $subscription?->package;
                abort_unless($owner->hasRole('owner') && $owner->is_approved && $package, 403);
                $limit = $package->max_bookings_per_month;
                $count = Booking::whereIn('salon_id', Salon::where('created_by', $owner->id)->select('id'))
                    ->where('created_at', '>=', now()->startOfMonth())->where('created_at', '<', now()->startOfMonth()->addMonth())
                    ->lockForUpdate()->get(['id'])->count();
                if ($limit !== null && $count >= $limit) {
                    throw ValidationException::withMessages(['salon_id' => 'The salon has reached its monthly booking limit.']);
                }
            }
            $data['status'] = $management ? ($data['status'] ?? 'pending') : 'pending';
            $this->validateSlot($salon, $data, null, ! $management);

            return Booking::create(Arr::only($data, ['salon_id', 'service_id', 'name', 'email', 'phone', 'date', 'time', 'status'])
                + ['user_id' => $management ? null : $actor?->id]);
        }, 3);
    }

    public function update(Booking $booking, array $changes, User $actor): Booking
    {
        $actor = $actor->fresh();
        abort_unless($actor, 403);
        Gate::forUser($actor)->authorize('update', $booking);
        $data = $this->validate(array_merge($booking->only(['salon_id', 'service_id', 'name', 'email', 'phone', 'date', 'time', 'status']), $changes));
        $salonIds = array_values(array_unique([(int) $booking->salon_id, (int) $data['salon_id']]));
        $ownerIds = Salon::whereIn('id', $salonIds)->pluck('created_by');

        return DB::transaction(function () use ($booking, $data, $actor, $salonIds, $ownerIds) {
            // Lock owners and salons in a stable order, including both sides of a move.
            User::whereIn('id', $ownerIds)->orderBy('id')->lockForUpdate()->get();
            $salons = Salon::whereIn('id', $salonIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($salons->contains(fn ($salon) => ! $ownerIds->contains($salon->created_by))) {
                throw ValidationException::withMessages(['salon_id' => 'The salon changed. Refresh and try again.'])->status(409);
            }
            $locked = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $fields = ['salon_id', 'service_id', 'name', 'email', 'phone', 'date', 'time', 'status'];
            if ($locked->only($fields) != $booking->only($fields) || $locked->updated_at?->ne($booking->updated_at)) {
                throw ValidationException::withMessages(['time' => 'This booking changed. Refresh and try again.'])->status(409);
            }
            $actor = $actor->fresh();
            abort_unless($actor, 403);
            Gate::forUser($actor)->authorize('update', $locked);
            $salon = $salons->get((int) $data['salon_id']);
            abort_unless($salon && ($actor->hasRole('admin') || (int) $salon->created_by === (int) $actor->id), 403);
            if ($locked->status === 'completed' && $data['status'] !== 'completed') {
                throw ValidationException::withMessages(['status' => 'Completed bookings cannot be reopened.']);
            }
            $this->validateSlot($salon, $data, $locked->id, false);
            $locked->update(Arr::only($data, ['salon_id', 'service_id', 'name', 'email', 'phone', 'date', 'time', 'status']));
            if ($actor->hasRole('admin') && $locked->wasChanged('status')) {
                DB::afterCommit(function () use ($locked) {
                    try {
                        $locked->loadMissing(['salon.owner', 'service']);
                        $locked->salon?->owner?->notify(new BookingStatusChangedNotification($locked));
                    } catch (\Throwable $exception) {
                        report($exception);
                    }
                });
            }

            return $locked;
        }, 3);
    }

    public function changeStatus(Booking $booking, string $status, User $actor): Booking
    {
        return $this->update($booking, ['status' => $status], $actor);
    }

    public function cancel(Booking $booking, User $customer): Booking
    {
        return DB::transaction(function () use ($booking, $customer) {
            $locked = Booking::whereKey($booking->id)->forCustomer($customer)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, ['pending', 'approved'], true)) {
                throw ValidationException::withMessages(['status' => 'This booking cannot be cancelled.']);
            }
            $locked->update(['status' => 'rejected']);
            DB::afterCommit(function () use ($locked) {
                try {
                    $locked->loadMissing(['salon.owner', 'service']);
                    $locked->salon?->owner?->notify(new BookingCancelledNotification($locked, 'customer', 'owner'));
                } catch (\Throwable $exception) {
                    report($exception);
                }
            });

            return $locked;
        }, 3);
    }

    public function remainingCapacity(Salon $salon, string $date, string $time, int $serviceId, ?int $ignoreId = null): int
    {
        $service = Service::findOrFail($serviceId);
        $start = $this->minutes($time);
        $end = $start + $this->duration($service->duration);
        $bookings = Booking::where('salon_id', $salon->id)->whereDate('date', $date)
            ->whereIn('status', ['pending', 'approved'])->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->when(DB::transactionLevel() > 0, fn ($query) => $query->lockForUpdate())->get();
        $durations = Service::withTrashed()->whereIn('id', $bookings->pluck('service_id'))->pluck('duration', 'id');

        return $this->capacity($salon, $bookings, $durations, $start, $end);
    }

    public function availableSlots(Salon $salon, string $date, Service $service): array
    {
        Validator::make(['date' => $date], ['date' => 'required|date_format:Y-m-d'])->validate();
        abort_unless((int) $service->created_by === (int) $salon->created_by, 404);
        $bookings = Booking::where('salon_id', $salon->id)->whereDate('date', $date)
            ->whereIn('status', ['pending', 'approved'])->get(['time', 'service_id']);
        $durations = Service::withTrashed()->whereIn('id', $bookings->pluck('service_id'))->pluck('duration', 'id');
        $open = $this->minutes($salon->open_time?->format('H:i') ?? '09:00');
        $close = $this->minutes($salon->close_time?->format('H:i') ?? '18:00');
        $duration = $this->duration($service->duration);
        $slots = [];
        for ($start = $open; $start + $duration <= $close; $start += 30) {
            $time = sprintf('%02d:%02d', intdiv($start, 60), $start % 60);
            if (Carbon::parse($date.' '.$time)->gt(now()) && $this->capacity($salon, $bookings, $durations, $start, $start + $duration) > 0) {
                $slots[] = $time;
            }
        }

        return $slots;
    }

    private function capacity(Salon $salon, $bookings, $durations, int $start, int $end): int
    {
        $events = [];
        foreach ($bookings as $booking) {
            $existingStart = $this->minutes($booking->time);
            $existingEnd = $existingStart + $this->duration($durations[$booking->service_id] ?? 30);
            if ($existingStart < $end && $existingEnd > $start) {
                $from = max($start, $existingStart);
                $to = min($end, $existingEnd);
                $events[$from] = ($events[$from] ?? 0) + 1;
                $events[$to] = ($events[$to] ?? 0) - 1;
            }
        }
        ksort($events, SORT_NUMERIC);
        $active = $peak = 0;
        foreach ($events as $change) {
            $active += $change;
            $peak = max($peak, $active);
        }

        return max(0, max(1, (int) $salon->number_of_staff) - $peak);
    }

    private function validate(array $data): array
    {
        // Stored times can include seconds; write one canonical minute precision value.
        if (is_string($data['time'] ?? null) && preg_match('/^\d{2}:\d{2}:00$/', $data['time'])) {
            $data['time'] = substr($data['time'], 0, 5);
        }
        if (($data['date'] ?? null) instanceof \DateTimeInterface) {
            $data['date'] = $data['date']->format('Y-m-d');
        }

        return Validator::make($data, ['salon_id' => 'required|integer|exists:salons,id',
            'service_id' => 'required|integer|exists:services,id', 'name' => 'required|string|max:255',
            'email' => 'required|email:rfc|max:255', 'phone' => 'required|string|max:20',
            'date' => 'required|date_format:Y-m-d', 'time' => 'required|date_format:H:i',
            'status' => 'sometimes|in:pending,approved,rejected,completed'])->validate();
    }

    private function validateSlot(Salon $salon, array $data, ?int $ignoreId, bool $public): void
    {
        $service = Service::findOrFail($data['service_id']);
        if ((int) $service->created_by !== (int) $salon->created_by) {
            throw ValidationException::withMessages(['service_id' => 'The selected service does not belong to this salon owner.']);
        }
        if ($public && $service->status !== 'published') {
            throw ValidationException::withMessages(['service_id' => 'This service is not available for public booking.']);
        }
        if (! in_array($data['status'] ?? 'pending', ['pending', 'approved'], true)) {
            return; // Historical completed/rejected records do not occupy capacity.
        }
        $at = Carbon::createFromFormat('Y-m-d H:i', $data['date'].' '.$data['time'])->startOfMinute();
        if ($public && $at->lte(now())) {
            throw ValidationException::withMessages(['time' => 'Choose a future appointment time.']);
        }
        $start = $this->minutes($data['time']);
        $open = $this->minutes($salon->open_time?->format('H:i') ?? '09:00');
        $close = $this->minutes($salon->close_time?->format('H:i') ?? '18:00');
        if ($start < $open || $start + $this->duration($service->duration) > $close || ($start - $open) % 30 !== 0) {
            throw ValidationException::withMessages(['time' => 'Choose a 30-minute slot within the salon opening hours, allowing the full service duration.']);
        }
        if ($this->remainingCapacity($salon, $data['date'], $data['time'], $service->id, $ignoreId) < 1) {
            throw ValidationException::withMessages(['time' => 'This time slot is fully booked.'])->status(409);
        }
    }

    private function lockSalon(int $id, int $ownerId): Salon
    {
        User::whereKey($ownerId)->lockForUpdate()->firstOrFail();
        $salon = Salon::whereKey($id)->lockForUpdate()->firstOrFail();
        if ((int) $salon->created_by !== (int) $ownerId) {
            throw ValidationException::withMessages(['salon_id' => 'The salon changed. Refresh and try again.'])->status(409);
        }

        return $salon;
    }

    private function minutes(string $time): int
    {
        return ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);
    }

    private function duration(?int $duration): int
    {
        return (int) ceil(max(30, (int) $duration) / 30) * 30;
    }
}
