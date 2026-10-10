<x-customer.layout title="Notifications">
    <div class="flex flex-wrap gap-4 items-center mb-6">
        <form method="POST" action="{{ route('customer.notifications.read-all') }}">@csrf<button class="button" type="submit">Mark all as read</button></form>
        <form method="POST" action="{{ route('customer.notifications.clear') }}">@csrf @method('DELETE')<button class="underline text-sm" type="submit">Clear all notifications</button></form>
    </div>
    <div class="space-y-4 mb-8">
        @forelse($notifications as $notification)
            <article class="panel p-6">
                <h2 class="font-semibold">{{ $notification->data['title'] ?? 'Booking update' }}</h2>
                <p class="mt-2">{{ $notification->data['message'] ?? 'You have an account update.' }}</p>
                <p class="muted text-sm mt-3">{{ $notification->created_at->format('j M Y, H:i') }} &middot; {{ $notification->read_at ? 'Read' : 'Unread' }}</p>
                <a href="{{ route('customer.upcoming') }}" class="underline text-sm inline-block mt-3">Upcoming bookings</a>
                <a href="{{ route('customer.history') }}" class="underline text-sm ml-4">Booking history</a>
                @unless($notification->read_at)<form method="POST" action="{{ route('customer.notifications.read', $notification->id) }}" class="mt-4">@csrf<button class="underline text-sm" type="submit">Mark as read</button></form>@endunless
                <form method="POST" action="{{ route('customer.notifications.delete', $notification->id) }}" class="mt-4">@csrf @method('DELETE')<button class="underline text-sm" type="submit">Delete notification</button></form>
            </article>
        @empty<p class="panel p-6">No notifications yet.</p>@endforelse
    </div>
    {{ $notifications->links() }}
</x-customer.layout>
