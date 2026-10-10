<x-customer.layout :title="$title">
    <p class="muted mb-6">{{ $upcoming ? 'Pending requests and approved appointments ahead of you.' : 'Past appointments and bookings that have been completed, cancelled or rejected.' }}</p>
    <div class="grid md:grid-cols-2 gap-5 mb-8">
        @forelse($bookings as $booking)<x-customer.booking :booking="$booking" :cancellable="$upcoming" />
        @empty<div class="panel p-8"><p>No {{ $upcoming ? 'upcoming bookings' : 'booking history' }} yet.</p><a class="button mt-5" href="{{ route('salons') }}">Browse salons</a></div>@endforelse
    </div>
    {{ $bookings->links() }}
</x-customer.layout>
