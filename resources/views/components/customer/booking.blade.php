@props(['booking', 'cancellable' => false])
<article class="panel p-6 space-y-4 customer-booking" data-booking-id="{{ $booking->id }}">
    <div class="flex flex-wrap justify-between gap-3">
        <div><h2 class="text-lg font-semibold">{{ $booking->salon?->name ?? 'Salon unavailable' }}</h2>
        <p class="muted mt-2">{{ $booking->service?->title ?? 'Service unavailable' }}</p></div>
        <span class="customer-booking-status" data-status="{{ $booking->status }}">{{ $booking->status === 'rejected' ? 'Cancelled / rejected' : ucfirst($booking->status) }}</span>
    </div>
    <p class="customer-booking-date">{{ $booking->date->format('D, j M Y') }} &middot; {{ substr($booking->time, 0, 5) }}</p>
    @if($booking->salon?->location)<p class="muted text-sm">{{ $booking->salon->location }}</p>@endif
    @if($booking->service)
        <p class="text-sm">{{ $booking->service->duration }} minutes &middot;
            @if($booking->salon?->show_price) LKR {{ number_format($booking->service->price, 2) }}
            @else Contact salon for pricing @endif
        </p>
    @endif
    <p class="customer-booking-reference">Booking #{{ $booking->id }}</p>
    @if($cancellable && in_array($booking->status, ['pending', 'approved'], true))
        <form method="POST" action="{{ route('customer.cancel', $booking->id) }}" x-data="{ confirming: false }" @submit="if (!confirming) { $event.preventDefault(); confirming = true; }">
            @csrf
            <button type="submit" class="button" x-show="!confirming">Cancel booking</button>
            <div x-show="confirming" x-cloak class="space-y-3"><p>Cancel this appointment?</p>
                <button type="submit" class="button">Yes, cancel booking</button>
                <button type="button" @click="confirming = false" class="underline ml-4">Keep booking</button>
            </div>
        </form>
    @endif
</article>
