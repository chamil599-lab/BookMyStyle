<x-customer.layout title="Your account">
    <p class="muted mb-8">Welcome, {{ auth()->user()->name }}. Your next appointment starts here.</p>
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-10">
        @foreach(['total_bookings' => 'Total bookings', 'upcoming_bookings' => 'Upcoming', 'completed_bookings' => 'Completed', 'cancelled_bookings' => 'Cancelled / rejected', 'favorite_salons' => 'Favorite salons'] as $key => $label)
            <div class="panel p-5"><p class="muted text-sm">{{ $label }}</p><p class="text-3xl font-semibold mt-3">{{ $stats[$key] }}</p></div>
        @endforeach
    </div>
    <div class="flex flex-wrap gap-4 justify-between items-center mb-6"><h2 class="text-xl font-semibold">Your next appointments</h2><a class="button" href="{{ route('salons') }}">Book a new appointment</a></div>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($bookings as $booking)<x-customer.booking :booking="$booking" />
        @empty<p class="panel p-6">No upcoming bookings. Find a salon for your next visit.</p>@endforelse
    </div>
    <a href="{{ route('customer.upcoming') }}" class="inline-block underline mt-6">View all upcoming bookings</a>
</x-customer.layout>
