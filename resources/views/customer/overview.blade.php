<x-customer.layout title="Your account">
    <section class="customer-welcome">
        <div><p class="eyebrow">Welcome, {{ auth()->user()->name }}</p><h2>A little time,<br><em>just for you.</em></h2><p>Keep track of your appointments, revisit your favourite places and make room for your next feel-good moment.</p></div>
        <div class="customer-welcome-date"><span>{{ now()->format('l') }}</span><strong>{{ now()->format('d') }}</strong><span>{{ now()->format('F Y') }}</span></div>
    </section>
    <div class="customer-stats">
        @foreach(['total_bookings' => 'Total bookings', 'upcoming_bookings' => 'Upcoming', 'completed_bookings' => 'Completed', 'cancelled_bookings' => 'Cancelled / rejected', 'favorite_salons' => 'Favorite salons'] as $key => $label)
            <div class="customer-stat"><p>{{ $label }}</p><strong>{{ number_format($stats[$key]) }}</strong></div>
        @endforeach
    </div>
    <div class="customer-section-heading"><div><p class="eyebrow">On your calendar</p><h2>Your next appointments</h2></div><a href="{{ route('customer.upcoming') }}">View all upcoming bookings <span aria-hidden="true">&rarr;</span></a></div>
    <div class="customer-appointments">
        @forelse($bookings as $booking)<x-customer.booking :booking="$booking" />
        @empty<div class="customer-empty"><span aria-hidden="true">&#10022;</span><h3>Your next good day is waiting.</h3><p>No upcoming bookings. Find a salon for your next visit.</p><a class="button" href="{{ route('home') }}#salons-section">Book a new appointment &nearr;</a></div>@endforelse
    </div>
    <div class="customer-shortcuts">
        <a href="{{ route('customer.favorites') }}"><p class="eyebrow">Your personal collection</p><h3>Places you love.</h3><p>Return to your saved salons and find your next appointment.</p><span>Explore favorites &rarr;</span></a>
        <a href="{{ route('customer.history') }}"><p class="eyebrow">Your beauty journal</p><h3>A look back.</h3><p>Revisit past appointments and the services you enjoyed.</p><span>View booking history &rarr;</span></a>
    </div>
</x-customer.layout>
