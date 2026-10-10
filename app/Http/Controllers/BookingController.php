<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Mail\BookingStatusChanged;
use Illuminate\Http\Request;
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

        if (!$booking) {
            return response()->json(['error' => 'Booking not found'], 404);
        }

        // Update status
        $booking->status = $validated['status'];
        $booking->save();

        // In-app notification to customer
        try {
            $booking->loadMissing(['salon', 'service', 'user']);
            if ($booking->user_id && $booking->user) {
                $booking->user->notify(new \App\Notifications\BookingStatusChangedNotification($booking));
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to send BookingStatusChangedNotification: ' . $e->getMessage());
        }

        // Send email notification
        try {
            Mail::to($booking->email)->send(new BookingStatusChanged($booking));
            
            return response()->json([
                'message' => "Booking {$validated['status']} and email sent successfully",
                'booking' => $booking
            ], 200);
            
        } catch (\Exception $e) {
            // Status updated but email failed
            return response()->json([
                'message' => "Booking {$validated['status']} but email failed to send",
                'booking' => $booking,
                'error' => $e->getMessage()
            ], 200); // Still 200 because booking was updated
        }
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
            \Log::error('Booking confirmation email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all bookings for a salon (for admin panel)
     */
    public function getSalonBookings($salonId)
    {
        $bookings = Booking::where('salon_id', $salonId)
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        return response()->json($bookings);
    }

    /**
     * Get user's bookings by email
     */
    public function getUserBookings(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $bookings = Booking::where('email', $validated['email'])
            ->with('salon:id,name,location') // Include salon details
            ->orderBy('date', 'desc')
            ->orderBy('time', 'desc')
            ->get();

        return response()->json($bookings);
    }
}