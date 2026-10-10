<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Salon;
use Illuminate\Http\JsonResponse;

class BookingApiController extends Controller
{
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $service = app(\App\Services\BookingService::class);
        try {
            $booking = $service->create($request->validated(), auth('sanctum')->user());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            if ($exception->status !== 409) {
                throw $exception;
            }

            return response()->json(['error' => $exception->getMessage(), 'message' => $exception->getMessage(),
                'errors' => $exception->errors(), 'available_slots' => 0,
                'staff_count' => max(1, (int) Salon::findOrFail($request->validated('salon_id'))->number_of_staff)], 409);
        }

        return response()->json(['message' => 'Booking created successfully', 'booking' => $booking,
            'remaining_slots' => $service->remainingCapacity($booking->salon, $booking->date->format('Y-m-d'), $booking->time, $booking->service_id),
            'is_registered' => $booking->user_id !== null], 201);
    }

    public function count(): JsonResponse
    {
        return response()->json(['count' => Booking::count()]);
    }
}
