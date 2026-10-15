@props(['salon'])
<article class="panel overflow-hidden salon-collection-card">
    <a href="{{ route('salon.show', $salon->id) }}" class="salon-collection-image" aria-label="{{ 'Explore '.$salon->name }}">
        <x-storefront.image :src="$salon->cover_url" alt="{{ $salon->name }}" loading="lazy" width="600" height="400" />
        <span class="salon-collection-rating">
            @if($salon->ratings_count > 0)
                <span aria-hidden="true">&#9733;</span> {{ number_format($salon->public_rating ?? 0, 1) }}
                <span class="salon-rating-count">({{ number_format($salon->ratings_count) }} {{ \Illuminate\Support\Str::plural('review', $salon->ratings_count) }})</span>
            @else
                No reviews yet
            @endif
        </span>
        <span class="salon-collection-arrow" aria-hidden="true">&nearr;</span>
    </a>
    <div class="salon-collection-body">
        <p class="salon-collection-location"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg>{{ $salon->location ?: 'Sri Lanka' }}</p>
        <h3><a href="{{ route('salon.show', $salon->id) }}">{{ $salon->name }}</a></h3>
        <p class="salon-collection-description">{{ \Illuminate\Support\Str::limit(strip_tags($salon->description ?? ''), 135) ?: 'Explore the salon, discover its treatments and plan a little time for yourself.' }}</p>
        <div class="salon-collection-facts">
            <span>{{ number_format($salon->published_services_count ?? 0) }} {{ \Illuminate\Support\Str::plural('service', $salon->published_services_count ?? 0) }}</span>
            @if($salon->number_of_staff > 0)<span>{{ number_format($salon->number_of_staff) }} team {{ \Illuminate\Support\Str::plural('member', $salon->number_of_staff) }}</span>@endif
        </div>
        <dl class="salon-collection-details">
            <div><dt>Opening hours</dt><dd>@if($salon->open_time && $salon->close_time){{ $salon->open_time->format('g:i A') }} &ndash; {{ $salon->close_time->format('g:i A') }}@else Contact salon for hours @endif</dd></div>
            <div><dt>Service pricing</dt><dd>@if($salon->show_price && $salon->starting_price !== null)From LKR {{ number_format((float) $salon->starting_price, 2) }}@else Contact salon for pricing @endif</dd></div>
        </dl>
        <a href="{{ route('salon.show', $salon->id) }}#booking" class="salon-collection-book"><span>Explore &amp; book</span><span aria-hidden="true">&rarr;</span></a>
    </div>
</article>
