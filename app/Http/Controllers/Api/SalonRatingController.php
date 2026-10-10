<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CanRateRequest;
use App\Http\Requests\Api\StoreSalonRatingRequest;
use App\Models\Salon;
use App\Models\SalonRating;
use App\Services\SalonReviewService;
use Illuminate\Http\JsonResponse;

class SalonRatingController extends Controller
{
    public function canRate(CanRateRequest $request, Salon $salon): JsonResponse
    {
        abort_unless(Salon::publiclyVisible()->whereKey($salon->id)->exists(), 404);

        return response()->json(app(SalonReviewService::class)->eligibility($salon, $request->user()));
    }

    public function index(Salon $salon): JsonResponse
    {
        abort_unless(Salon::publiclyVisible()->whereKey($salon->id)->exists(), 404);
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
        $rating = app(SalonReviewService::class)->submit($salon, $request->user(), $request->validated());

        return response()->json([
            'message' => 'Rating submitted successfully',
            'rating' => $rating,
        ], 201);
    }
}
