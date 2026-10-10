<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CanRateRequest;
use App\Http\Requests\Api\StoreSalonRatingRequest;
use App\Models\Booking;
use App\Models\Salon;
use App\Models\SalonRating;
use Illuminate\Http\JsonResponse;

class SalonRatingController extends Controller
{
    public function canRate(CanRateRequest $request, Salon $salon): JsonResponse
    {
        $validated = $request->validated();

        $email = $validated['email'];

        $hasApprovedBooking = Booking::where('salon_id', $salon->id)
            ->where('email', $email)
            ->where('status', Booking::STATUS_APPROVED)
            ->exists();

        $hasRated = SalonRating::where('salon_id', $salon->id)
            ->where('email', $email)
            ->exists();

        return response()->json([
            'can_rate' => $hasApprovedBooking && ! $hasRated,
            'has_booking' => $hasApprovedBooking,
            'already_rated' => $hasRated,
            'message' => $hasApprovedBooking
                ? ($hasRated ? 'You have already rated this salon' : 'You can rate this salon')
                : 'You must book and have an approved appointment to rate this salon',
        ]);
    }

    public function index(Salon $salon): JsonResponse
    {
        $ratings = SalonRating::where('salon_id', $salon->id)
            ->orderByDesc('created_at')
            ->get();

        $count = $ratings->count();
        $average = $count > 0 ? round((float) $ratings->avg('rating'), 1) : 0;

        return response()->json([
            'ratings' => $ratings,
            'average' => $average,
            'count' => $count,
        ]);
    }

    public function store(StoreSalonRatingRequest $request, Salon $salon): JsonResponse
    {
        $validated = $request->validated();

        $hasApprovedBooking = Booking::where('salon_id', $salon->id)
            ->where('email', $validated['email'])
            ->where('status', Booking::STATUS_APPROVED)
            ->exists();

        if (! $hasApprovedBooking) {
            return response()->json([
                'error' => 'You must have an approved booking at this salon before rating.',
                'can_rate' => false,
            ], 403);
        }

        $existingRating = SalonRating::where('salon_id', $salon->id)
            ->where('email', $validated['email'])
            ->first();

        if ($existingRating) {
            return response()->json([
                'error' => 'You have already rated this salon.',
                'existing_rating' => $existingRating,
            ], 409);
        }

        $rating = SalonRating::create([
            'salon_id' => $salon->id,
            'email' => $validated['email'],
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        return response()->json([
            'message' => 'Rating submitted successfully',
            'rating' => $rating,
        ], 201);
    }
}
