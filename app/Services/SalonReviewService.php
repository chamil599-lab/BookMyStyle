<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Salon;
use App\Models\SalonRating;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SalonReviewService
{
    public function eligibility(Salon $salon, User $user): array
    {
        $booked = Booking::where('salon_id', $salon->id)->forCustomer($user)->whereIn('status', ['approved', 'completed'])->exists();
        $rated = SalonRating::where('salon_id', $salon->id)->where('email', $user->email)->exists();

        return ['can_rate' => $user->hasVerifiedEmail() && $booked && ! $rated, 'has_booking' => $booked, 'already_rated' => $rated,
            'message' => ! $user->hasVerifiedEmail() ? 'Verify your email before reviewing.' : ($rated ? 'You have already reviewed this salon.' : ($booked ? 'You can review this salon.' : 'An approved or completed booking is required to review this salon.'))];
    }

    public function submit(Salon $salon, User $user, array $data): SalonRating
    {
        $data = Validator::make($data, ['rating' => 'required|integer|min:1|max:5', 'review' => 'nullable|string|max:1000'])->validate();
        try {
            return DB::transaction(function () use ($salon, $user, $data) {
                $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                abort_unless(Salon::publiclyVisible()->whereKey($salon->id)->exists(), 404);
                $eligibility = $this->eligibility($salon, $user);
                if ($eligibility['already_rated']) {
                    throw ValidationException::withMessages(['rating' => $eligibility['message']])->status(409);
                }
                abort_unless($eligibility['can_rate'], 403, $eligibility['message']);

                return SalonRating::create(['salon_id' => $salon->id, 'email' => $user->email, 'rating' => $data['rating'], 'review' => $data['review'] ?? null]);
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages(['rating' => 'You have already reviewed this salon.'])->status(409);
        }
    }
}
