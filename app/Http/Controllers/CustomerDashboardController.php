<?php

namespace App\Http\Controllers;

use App\Services\BookingService;
use App\Services\CustomerDashboard;
use App\Services\FavoriteService;
use Illuminate\Http\Request;

class CustomerDashboardController extends Controller
{
    public function overview(Request $request, CustomerDashboard $dashboard)
    {
        return response()->view('customer.overview', ['stats' => $dashboard->stats($request->user()),
            'bookings' => $dashboard->upcoming($request->user())->limit(3)->get()]);
    }

    public function upcoming(Request $request, CustomerDashboard $dashboard)
    {
        $request->validate(['page' => 'nullable|integer|min:1|max:100000']);

        return view('customer.bookings', ['title' => 'Upcoming bookings', 'upcoming' => true,
            'bookings' => $dashboard->upcoming($request->user())->paginate(10)]);
    }

    public function history(Request $request, CustomerDashboard $dashboard)
    {
        $request->validate(['page' => 'nullable|integer|min:1|max:100000']);

        return view('customer.bookings', ['title' => 'Booking history', 'upcoming' => false,
            'bookings' => $dashboard->history($request->user())->paginate(10)]);
    }

    public function cancel(Request $request, int $id, CustomerDashboard $dashboard, BookingService $service)
    {
        $booking = $dashboard->bookings($request->user())->findOrFail($id);
        $service->cancel($booking, $request->user());

        return redirect()->route('customer.upcoming')->with('status', 'Booking cancelled.');
    }

    public function favorites(Request $request, FavoriteService $favorites)
    {
        $request->validate(['page' => 'nullable|integer|min:1|max:100000']);

        return view('customer.favorites', ['salons' => $favorites->salons($request->user())->orderBy('name')->orderBy('id')->paginate(12)]);
    }

    public function removeFavorite(Request $request, int $id, FavoriteService $favorites)
    {
        abort_unless($favorites->remove($request->user(), $id), 404);

        return redirect()->route('customer.favorites')->with('status', 'Salon removed from favorites.');
    }

    public function notifications(Request $request)
    {
        $request->validate(['page' => 'nullable|integer|min:1|max:100000']);

        return view('customer.notifications', ['notifications' => $request->user()->notifications()->paginate(15)]);
    }

    public function readNotification(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return redirect()->route('customer.notifications')->with('status', 'Notification marked as read.');
    }

    public function readAllNotifications(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return redirect()->route('customer.notifications')->with('status', 'Notifications marked as read.');
    }

    public function deleteNotification(Request $request, string $id)
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->delete();

        return redirect()->route('customer.notifications')->with('status', 'Notification deleted.');
    }

    public function deleteAllNotifications(Request $request)
    {
        $request->user()->notifications()->delete();

        return redirect()->route('customer.notifications')->with('status', 'Notifications cleared.');
    }
}
