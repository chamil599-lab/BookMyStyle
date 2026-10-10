<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BookingApiController extends Controller
{
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        return DB::transaction(function () use ($validated) {
            $salon = Salon::whereKey($validated['salon_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $service = Service::where('id', $validated['service_id'])
                ->where(function ($query) use ($salon) {
                    $query->where('created_by', $salon->created_by)
                        ->orWhere('updated_by', $salon->created_by);
                })
                ->first();

            if (! $service) {
                return response()->json([
                    'error' => 'The selected service is not available for this salon.',
                ], 422);
            }

            $staffCount = max(1, (int) ($salon->number_of_staff ?? 1));
            $duration = max(30, (int) ($service->duration ?? 30));
            $slotsNeeded = (int) ceil($duration / 30);

            $existingBookings = Booking::where('salon_id', $validated['salon_id'])
                ->whereDate('date', $validated['date'])
                ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_APPROVED])
                ->lockForUpdate()
                ->get(['time', 'service_id']);

            $durations = Service::whereIn('id', $existingBookings->pluck('service_id')->filter()->unique())
                ->pluck('duration', 'id');

            $requestedMinutes = $this->toMinutes($validated['time']);
            $overlappingCount = 0;

            foreach ($existingBookings as $booking) {
                $existingDuration = max(30, (int) ($durations[$booking->service_id] ?? 30));
                $existingSlots = (int) ceil($existingDuration / 30);
                $existingStart = $this->toMinutes($booking->time);

                if ($this->hasOverlap($requestedMinutes, $slotsNeeded, $existingStart, $existingSlots)) {
                    $overlappingCount++;
                }
            }

            if ($overlappingCount >= $staffCount) {
                return response()->json([
                    'error' => 'This time slot is fully booked. Please choose another time.',
                    'available_slots' => 0,
                    'staff_count' => $staffCount,
                ], 409);
            }

            $bookingData = [
                'salon_id' => $validated['salon_id'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'service_id' => $service->id,
                'date' => $validated['date'],
                'time' => $validated['time'],
                'status' => Booking::STATUS_PENDING,
            ];

            if (auth('sanctum')->check()) {
                $bookingData['user_id'] = auth('sanctum')->id();
            }

            $booking = Booking::create($bookingData);

            try {
                $owner = $booking->salon?->owner;
                if ($owner) {
                    $owner->notify(new \App\Notifications\BookingCreatedNotification($booking));
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to send BookingCreatedNotification: ' . $e->getMessage());
            }

            return response()->json([
                'message' => 'Booking created successfully',
                'booking' => $booking,
                'remaining_slots' => max(0, $staffCount - $overlappingCount - 1),
                'is_registered' => isset($bookingData['user_id']),
            ], 201);
        });
    }

    public function count(): JsonResponse
    {
        return response()->json(['count' => Booking::count()]);
    }

    private function toMinutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return ($hours * 60) + $minutes;
    }

    private function hasOverlap(int $startA, int $slotsA, int $startB, int $slotsB): bool
    {
        $endA = $startA + ($slotsA * 30);
        $endB = $startB + ($slotsB * 30);

        return $startA < $endB && $endA > $startB;
    }
}
