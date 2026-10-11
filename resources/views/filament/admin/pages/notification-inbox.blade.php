<x-filament-panels::page>
    <div wire:poll.30s.visible>
        <div class="flex flex-wrap gap-4 mb-6">
            <x-filament::button wire:click="readAll">Mark all as read</x-filament::button>
            <x-filament::button color="gray" wire:click="clearAll" wire:confirm="Clear all your notifications?">Clear all notifications</x-filament::button>
        </div>
        <div class="space-y-4 mb-6">
            @forelse($this->notifications as $notification)
                <article wire:key="notification-{{ $notification->id }}" class="rounded-xl border border-gray-200 dark:border-gray-700 p-5" data-notification-id="{{ $notification->id }}">
                    <h2 class="font-semibold">{{ is_string($notification->data['title'] ?? null) ? $notification->data['title'] : 'Account update' }}</h2>
                    <p class="mt-2">{{ is_string($notification->data['message'] ?? null) ? $notification->data['message'] : 'You have an account update.' }}</p>
                    <p class="text-sm mt-2">{{ $notification->created_at->format('j M Y, H:i') }} · {{ $notification->read_at ? 'Read' : 'Unread' }}</p>
                    <div class="flex flex-wrap gap-4 mt-4 text-sm">
                        <a class="underline" href="{{ app(\App\Services\AccountNotifications::class)->destination(auth()->user(), $notification->data) }}">View details</a>
                        @unless($notification->read_at)<button type="button" class="underline" wire:click="readNotification('{{ $notification->id }}')">Mark as read</button>@endunless
                        <button type="button" class="underline" wire:click="deleteNotification('{{ $notification->id }}')">Delete notification</button>
                    </div>
                </article>
            @empty<p>No notifications yet.</p>@endforelse
        </div>
        {{ $this->notifications->links() }}
    </div>
</x-filament-panels::page>
