<?php

namespace App\Http\Controllers;

use App\Mail\BookingStatusChanged;
use App\Models\Booking;
use App\Models\Salon;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class BookingController extends Controller
{
    /**
     * Update booking status (approve/reject) and send email notification
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $booking = Booking::find($id);

        if (! $booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        Gate::forUser(auth()->user()->fresh())->authorize('updateStatus', $booking);

        $booking = app(BookingService::class)->changeStatus($booking, $validated['status'], $request->user());

        return response()->json([
            'message' => "Booking {$validated['status']} successfully",
            'booking' => $booking,
        ]);
    }

    /**
     * Send confirmation email immediately after booking creation
     */
    public function sendConfirmationEmail(Booking $booking)
    {
        try {
            Mail::to($booking->email)->send(new BookingStatusChanged($booking));

            return true;
        } catch (\Exception $e) {
            \Log::error('Booking confirmation email failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Get all bookings for a salon (for admin panel)
     */
    public function getSalonBookings($salonId)
    {
        Gate::forUser(auth()->user()->fresh())->authorize('view-salon-bookings', Salon::findOrFail($salonId));
        $bookings = Booking::where('salon_id', $salonId)
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        return response()->json($bookings);
    }

    /**
     * Get only bookings belonging to the authenticated account.
     */
    public function getUserBookings(Request $request)
    {
        $bookings = Booking::forCustomer($request->user())
            ->with('salon:id,name,location') // Include salon details
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        return response()->json($bookings);
    }
}
