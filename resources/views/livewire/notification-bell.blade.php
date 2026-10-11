<div wire:poll.30s.visible>
    <a href="{{ $inboxUrl }}" aria-label="Notifications: {{ $unread }} unread" class="inline-flex items-center gap-1 px-2 py-2" data-notification-bell>
        <svg aria-hidden="true" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
        @if($unread)<span class="text-xs font-semibold" data-unread-count>{{ $unread > 99 ? '99+' : $unread }}</span>@endif
    </a>
</div>
