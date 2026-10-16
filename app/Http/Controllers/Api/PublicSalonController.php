<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SalonHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PublicSalonsIndexRequest;
use App\Http\Requests\Api\SalonBookedTimesRequest;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\Service;
use App\Services\PublicCatalogue;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class PublicSalonController extends Controller
{
    public function index(PublicSalonsIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $departmentId = $validated['department_id'] ?? null;

        if ($departmentId) {
            $salons = app(PublicCatalogue::class)->salons()
                ->whereHas('services', fn ($query) => $query->published()->whereHas('department', fn ($query) => $query->whereKey($departmentId)->where('is_active', true)))
                ->get();

            return response()->json($salons->map(fn ($salon) => SalonHelper::formatSalonWithMedia($salon, true)));
        }

        $salons = app(PublicCatalogue::class)->salons()->get();

        return response()->json($salons->map(fn ($salon) => SalonHelper::formatSalonWithMedia($salon, true)));
    }

    public function simpleList(): JsonResponse
    {
        return response()->json(Salon::publiclyVisible()->select('id', 'name', 'location')->get());
    }

    public function show(Salon $salon): JsonResponse
    {
        abort_unless(Salon::publiclyVisible()->whereKey($salon->id)->exists(), 404);

        return response()->json(SalonHelper::formatSalonWithMedia($salon, true));
    }

    public function services(Salon $salon): JsonResponse
    {
        abort_unless(Salon::publiclyVisible()->whereKey($salon->id)->exists(), 404);
        $services = Service::published()->where('created_by', $salon->created_by)
            ->with('department')
            ->get();

        if ($services->isEmpty()) {
            return response()->json(['message' => 'No services found for this salon owner'], 404);
        }

        return response()->json($services->map(fn ($service) => SalonHelper::publicService($service, $salon)));
    }

    public function workingHours(Salon $salon): JsonResponse
    {
        abort_unless(Salon::publiclyVisible()->whereKey($salon->id)->exists(), 404);

        return response()->json([
            'open_time' => $salon->open_time?->format('H:i') ?? '09:00',
            'close_time' => $salon->close_time?->format('H:i') ?? '18:00',
            'number_of_staff' => $salon->number_of_staff ?? 1,
        ]);
    }

    public function bookedTimes(SalonBookedTimesRequest $request, Salon $salon): JsonResponse
    {
        abort_unless(Salon::publiclyVisible()->whereKey($salon->id)->exists(), 404);
        $validated = $request->validated();

        $bookings = Booking::where('salon_id', $salon->id)
            ->whereDate('date', $validated['date'])
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_APPROVED])
            ->get(['time', 'service_id', 'status']);

        $durations = Service::whereIn('id', $bookings->pluck('service_id')->filter()->unique())
            ->pluck('duration', 'id');

        $bookedTimes = $bookings->map(function (Booking $booking) use ($durations) {
            return [
                'time' => $booking->time,
                'service_id' => $booking->service_id,
                'duration' => (int) ($durations[$booking->service_id] ?? 30),
                'status' => $booking->status,
            ];
        })->values();

        return response()->json([
            'booked_times' => $bookedTimes,
            'staff_count' => $salon->number_of_staff ?? 1,
        ]);
    }

    public function bookedTimesLegacy(Salon $salon, string $date): JsonResponse
    {
        abort_unless(Salon::publiclyVisible()->whereKey($salon->id)->exists(), 404);
        if (! Carbon::hasFormat($date, 'Y-m-d')) {
            return response()->json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 422);
        }

        $bookedTimes = Booking::where('salon_id', $salon->id)
            ->whereDate('date', $date)
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_APPROVED])
            ->pluck('time');

        return response()->json($bookedTimes);
    }
}
