<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FavoriteSalon;
use App\Models\Salon;
use App\Models\User;
use App\Helpers\SalonHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Get upcoming bookings for authenticated customer
     */
    public function upcomingBookings(Request $request)
    {
        $user = Auth::user();

        $bookings = Booking::with(['salon', 'service'])
            ->forCustomer($user)
            ->upcoming()
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'salon' => [
                        'id' => $booking->salon?->id,
                        'name' => $booking->salon?->name,
                        'address' => $booking->salon?->location ?? null,
                    ],
                    'service' => [
                        'id' => $booking->service?->id,
                        'name' => $booking->service?->title,
                        'price' => $booking->service?->price,
                        'duration' => $booking->service?->duration,
                    ],
                    'date' => $booking->date->format('Y-m-d'),
                    'time' => $booking->time,
                    'status' => $booking->status,
                    'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Get booking history for authenticated customer
     */
    public function bookingHistory(Request $request)
    {
        $user = Auth::user();

        $bookings = Booking::with(['salon', 'service'])
            ->forCustomer($user)
            ->past()
            ->paginate(10);

        $data = $bookings->map(function ($booking) {
            return [
                'id' => $booking->id,
                'salon' => [
                    'id' => $booking->salon?->id,
                    'name' => $booking->salon?->name,
                    'address' => $booking->salon?->location ?? null,
                ],
                'service' => [
                    'id' => $booking->service?->id,
                    'name' => $booking->service?->title,
                    'price' => $booking->service?->price,
                    'duration' => $booking->service?->duration,
                ],
                'date' => $booking->date->format('Y-m-d'),
                'time' => $booking->time,
                'status' => $booking->status,
                'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ]
        ]);
    }

    /**
     * Get customer profile
     */
    public function profile(Request $request)
    {
        $user = Auth::user();

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ]
        ]);
    }

    /**
     * Update customer profile
     */
    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . Auth::id(),
        ]);

        /** @var User $user */
        $user = Auth::user();
        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $bookingStats = Booking::forCustomer($user)
            ->selectRaw(
                'COUNT(*) as total_bookings,
                SUM(CASE WHEN date >= ? THEN 1 ELSE 0 END) as upcoming_bookings,
                SUM(CASE WHEN date < ? AND status = ? THEN 1 ELSE 0 END) as completed_bookings,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as cancelled_bookings',
                [$today, $today, Booking::STATUS_APPROVED, Booking::STATUS_REJECTED]
            )
            ->first();

        $favoriteCount = FavoriteSalon::where('user_id', $user->id)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_bookings' => (int) ($bookingStats->total_bookings ?? 0),
                'upcoming_bookings' => (int) ($bookingStats->upcoming_bookings ?? 0),
                'completed_bookings' => (int) ($bookingStats->completed_bookings ?? 0),
                'cancelled_bookings' => (int) ($bookingStats->cancelled_bookings ?? 0),
                'favorite_salons' => $favoriteCount,
            ]
        ]);
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(Request $request, $id)
    {
        $booking = Booking::where('id', $id)
            ->forCustomer(Auth::user())
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        // Only allow cancellation of pending or approved bookings
        if (!in_array($booking->status, ['pending', 'approved'])) {
            return response()->json([
                'success' => false,
                'message' => 'This booking cannot be cancelled'
            ], 400);
        }

        $booking->update(['status' => 'rejected']);

        try {
            $fresh = $booking->fresh(['salon.owner', 'service']);
            if ($fresh && $fresh->salon?->owner) {
                $fresh->salon->owner->notify(new \App\Notifications\BookingCancelledNotification($fresh, 'customer', 'owner'));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send BookingCancelledNotification: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking cancelled successfully'
        ]);
    }

    /**
     * Get all favorite salons for authenticated customer
     */
    public function getFavorites(Request $request)
    {
        $favorites = FavoriteSalon::with('salon')
            ->where('user_id', Auth::id())
            ->get()
            ->map(function ($favorite) {
                $data = SalonHelper::formatSalonWithMedia($favorite->salon, true);

                // Backwards-compatible fields for frontend
                $data['image'] = $data['cover_url'] ?? ($data['cover_thumb_url'] ?? null);
                $data['address'] = $data['location'] ?? null;

                return $data;
            });

        return response()->json([
            'success' => true,
            'data' => $favorites
        ]);
    }

    /**
     * Add salon to favorites
     */
    public function addFavorite(Request $request, $salonId)
    {
        $salon = Salon::find($salonId);

        if (!$salon) {
            return response()->json([
                'success' => false,
                'message' => 'Salon not found'
            ], 404);
        }

        // Check if already in favorites
        $exists = FavoriteSalon::where('user_id', Auth::id())
            ->where('salon_id', $salonId)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Salon is already in your favorites'
            ], 400);
        }

        FavoriteSalon::create([
            'user_id' => Auth::id(),
            'salon_id' => $salonId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Salon added to favorites successfully'
        ], 201);
    }

    /**
     * Remove salon from favorites
     */
    public function removeFavorite(Request $request, $salonId)
    {
        $favorite = FavoriteSalon::where('user_id', Auth::id())
            ->where('salon_id', $salonId)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Salon not found in favorites'
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Salon removed from favorites successfully'
        ]);
    }

    /**
     * Check if salon is in favorites
     */
    public function checkFavorite(Request $request, $salonId)
    {
        $isFavorite = FavoriteSalon::where('user_id', Auth::id())
            ->where('salon_id', $salonId)
            ->exists();

        return response()->json([
            'success' => true,
            'favorited' => (bool) $isFavorite
        ]);
    }

    /**
     * Toggle favorite status for a salon (add/remove)
     */
    public function toggleFavorite(Request $request, $salonId)
    {
        $salon = Salon::find($salonId);

        if (!$salon) {
            return response()->json([
                'success' => false,
                'message' => 'Salon not found'
            ], 404);
        }

        $favorite = FavoriteSalon::where('user_id', Auth::id())
            ->where('salon_id', $salonId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json([
                'success' => true,
                'favorited' => false,
                'message' => 'Removed from favorites'
            ]);
        }

        FavoriteSalon::create([
            'user_id' => Auth::id(),
            'salon_id' => $salonId,
        ]);

        return response()->json([
            'success' => true,
            'favorited' => true,
            'message' => 'Added to favorites'
        ], 201);
    }
}
