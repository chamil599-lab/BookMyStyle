@props(['salon'])
<article class="panel overflow-hidden group">
    <a href="{{ route('salon.show', $salon->id) }}" class="block overflow-hidden">
        <img src="{{ \App\Support\PublicUrl::image($salon->cover_url) }}" alt="{{ $salon->name }}" class="w-full aspect-[4/3] object-cover transition duration-300 group-hover:scale-105" loading="lazy" width="600" height="450">
    </a>
    <div class="p-6">
        <div class="flex justify-between gap-3 mb-3"><span class="eyebrow">{{ $salon->location ?: 'Sri Lanka' }}</span><span class="text-xs shrink-0">★ {{ number_format($salon->public_rating ?? 0, 1) }} <span class="muted">({{ $salon->ratings_count }})</span></span></div>
        <h3 class="text-lg font-semibold mb-3"><a href="{{ route('salon.show', $salon->id) }}">{{ $salon->name }}</a></h3>
        <p class="muted text-sm leading-6 min-h-12">{{ \Illuminate\Support\Str::limit(strip_tags($salon->description ?? ''), 110) }}</p>
        <div class="mt-6 flex items-center justify-between gap-3 border-t border-[var(--line)] pt-4 text-xs"><span class="muted">{{ $salon->open_time?->format('H:i') ?? '09:00' }} – {{ $salon->close_time?->format('H:i') ?? '18:00' }}</span><a href="{{ route('salon.show', $salon->id) }}#booking" class="font-bold text-[var(--terra)]">Book now →</a></div>
    </div>
</article>
