<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\BookingWhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingWhatsAppController extends Controller
{
    public function __invoke(Request $request, Booking $booking, BookingWhatsApp $whatsApp): RedirectResponse
    {
        $url = $whatsApp->url($request->user(), $booking);
        abort_if($url === null, 404);

        return redirect()->away($url);
    }
}
