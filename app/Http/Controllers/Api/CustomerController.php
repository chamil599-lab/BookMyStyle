<?php

namespace App\Http\Controllers\Api;

use App\Helpers\SalonHelper;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\CustomerDashboard;
use App\Services\FavoriteService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function upcomingBookings(Request $request, CustomerDashboard $dashboard)
    {
        return response()->json(['success' => true,
            'data' => $dashboard->upcoming($request->user())->get()->map(fn ($booking) => $this->bookingPayload($booking))]);
    }

    public function bookingHistory(Request $request, CustomerDashboard $dashboard)
    {
        $request->validate(['page' => 'nullable|integer|min:1|max:100000']);
        $bookings = $dashboard->history($request->user())->paginate(10);

        return response()->json(['success' => true,
            'data' => $bookings->getCollection()->map(fn ($booking) => $this->bookingPayload($booking)),
            'meta' => ['current_page' => $bookings->currentPage(), 'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(), 'total' => $bookings->total()]]);
    }

    private function bookingPayload(Booking $booking): array
    {
        return ['id' => $booking->id,
            'salon' => ['id' => $booking->salon?->id, 'name' => $booking->salon?->name, 'address' => $booking->salon?->location],
            'service' => ['id' => $booking->service?->id, 'name' => $booking->service?->title,
                'price' => $booking->salon?->show_price ? $booking->service?->price : null, 'duration' => $booking->service?->duration],
            'date' => $booking->date->format('Y-m-d'), 'time' => $booking->time, 'status' => $booking->status,
            'created_at' => $booking->created_at->format('Y-m-d H:i:s')];
    }

    public function profile(Request $request)
    {
        $data = $request->user()->only(['id', 'name', 'email', 'email_verified_at']);
        $data['created_at'] = $request->user()->created_at->format('Y-m-d H:i:s');

        return response()->json(['success' => true,
            'data' => $data]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$request->user()->id]);
        $request->user()->update($validated);

        return response()->json(['success' => true, 'message' => 'Profile updated successfully',
            'data' => $request->user()->only(['id', 'name', 'email'])]);
    }

    public function stats(Request $request, CustomerDashboard $dashboard)
    {
        return response()->json(['success' => true, 'data' => $dashboard->stats($request->user())]);
    }

    public function cancelBooking(Request $request, int $id, BookingService $service)
    {
        $booking = Booking::forCustomer($request->user())->findOrFail($id);
        if (! in_array($booking->status, ['pending', 'approved'], true)) {
            return response()->json(['success' => false, 'message' => 'This booking cannot be cancelled'], 400);
        }
        $service->cancel($booking, $request->user());

        return response()->json(['success' => true, 'message' => 'Booking cancelled successfully']);
    }

    public function getFavorites(Request $request, FavoriteService $favorites)
    {
        $data = $favorites->salons($request->user())->orderBy('name')->orderBy('id')->get()->map(function ($salon) {
            $data = SalonHelper::formatSalonWithMedia($salon, true);
            $data['image'] = $data['cover_url'] ?? $data['cover_thumb_url'] ?? null;
            $data['address'] = $data['location'] ?? null;

            return $data;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function addFavorite(Request $request, int $salonId, FavoriteService $favorites)
    {
        if (! $favorites->add($request->user(), $salonId)) {
            return response()->json(['success' => false, 'message' => 'Salon is already in your favorites'], 400);
        }

        return response()->json(['success' => true, 'message' => 'Salon added to favorites successfully'], 201);
    }

    public function removeFavorite(Request $request, int $salonId, FavoriteService $favorites)
    {
        abort_unless($favorites->remove($request->user(), $salonId), 404);

        return response()->json(['success' => true, 'message' => 'Salon removed from favorites successfully']);
    }

    public function checkFavorite(Request $request, int $salonId, FavoriteService $favorites)
    {
        return response()->json(['success' => true, 'favorited' => $favorites->salons($request->user())->whereKey($salonId)->exists()]);
    }

    public function toggleFavorite(Request $request, int $salonId, FavoriteService $favorites)
    {
        $favorited = $favorites->toggle($request->user(), $salonId);

        return response()->json(['success' => true, 'favorited' => $favorited,
            'message' => $favorited ? 'Added to favorites' : 'Removed from favorites'], $favorited ? 201 : 200);
    }
}
