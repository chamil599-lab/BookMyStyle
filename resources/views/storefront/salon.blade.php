<x-storefront.layout :title="$title" :description="\Illuminate\Support\Str::limit(strip_tags($salon->description ?? 'Explore services and book your appointment at '.$salon->name), 155)">
    <div class="salon-page shell">
        <nav class="salon-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span aria-hidden="true">/</span><a href="{{ route('salons') }}">Salons</a><span aria-hidden="true">/</span><span aria-current="page">{{ $salon->name }}</span></nav>
        <section class="salon-intro" aria-labelledby="salon-heading">
            <div class="salon-cover">
                <x-storefront.image :src="$salon->cover_url" :alt="$salon->name" loading="eager" fetchpriority="high" width="1000" height="800" />
                <span class="salon-cover-label">THE SALON EDIT <span aria-hidden="true">&#10035;</span></span>
                <div class="salon-cover-caption"><span>Your next<br><em>feel-good place.</em></span></div>
            </div>
            <div class="salon-intro-copy">
                <p class="luxury-kicker">BEAUTY, MADE PERSONAL</p><h1 id="salon-heading">{{ $salon->name }}</h1>
                <p class="salon-location"><span aria-hidden="true">&#8982;</span> {{ $salon->location ?: 'Sri Lanka' }}</p>
                <a href="#reviews" class="salon-rating">@if($salon->ratings_count)<span class="salon-rating-stars" aria-hidden="true">&#9733;</span><strong>{{ number_format($salon->public_rating ?? 0, 1) }}</strong><span>{{ $salon->ratings_count }} {{ \Illuminate\Support\Str::plural('review', $salon->ratings_count) }}</span>@else<span class="salon-rating-stars" aria-hidden="true">&#9734;</span><span>No reviews yet. Your experience could be the first.</span>@endif<span aria-hidden="true">&nearr;</span></a>
                @if($salon->description)<p class="salon-description">{{ strip_tags($salon->description) }}</p>@endif
                <div class="salon-facts"><div><span>THE TREATMENTS</span><strong>{{ $salon->services->count() }} {{ \Illuminate\Support\Str::plural('service', $salon->services->count()) }} to explore</strong></div><div><span>OPENING HOURS</span><strong>@if($salon->open_time && $salon->close_time){{ $salon->open_time->format('H:i') }} &ndash; {{ $salon->close_time->format('H:i') }}@else Contact for hours @endif</strong></div></div>
                <a href="#booking" class="button salon-book-link">Request appointment <span aria-hidden="true">&nearr;</span></a><p class="salon-confirmation-note">Choose your treatment and time. The salon confirms your request.</p>
            </div>
        </section>
        <nav aria-label="Salon sections" class="salon-section-nav"><a href="#services">Treatments <span>{{ $salon->services->count() }}</span></a><a href="#booking">Request a booking</a><a href="#gallery">The space</a><a href="#contact">Visit & contact</a><a href="#reviews">Reviews <span>{{ $salon->ratings_count }}</span></a></nav>
        <div class="salon-main-grid">
            <div class="salon-main-content">
                <section id="services" class="salon-section" x-data="{ category: 'all' }" aria-labelledby="services-heading">
                    <div class="salon-section-heading"><div><p class="luxury-kicker">A LITTLE SOMETHING FOR YOU</p><h2 id="services-heading">Find your <em>feel-good.</em></h2></div></div>
                    <div class="salon-service-filter"><label for="service-category">Explore treatments</label><select id="service-category" x-model="category" class="field"><option value="all">All services</option>@foreach($salon->services->pluck('department')->filter()->unique('id') as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select></div>
                    <div class="salon-service-list">
                        @forelse($salon->services as $service)
                            <article class="salon-service panel" x-show="category === 'all' || category === '{{ $service->department_id }}'">
                                <div class="salon-service-copy"><p class="luxury-kicker">{{ $service->category?->name ?? $service->department?->name ?? 'General' }}</p><h3>{{ $service->title }}</h3>@if($service->description)<p class="muted">{{ strip_tags($service->description) }}</p>@endif<span class="salon-duration">{{ $service->duration ?? 30 }} minutes</span></div>
                                <div class="salon-service-price">@if($salon->show_price)<span>LKR</span><strong>{{ number_format((float) $service->price, 2) }}</strong>@else<span>Pricing</span><strong class="salon-price-on-request">Contact salon for pricing</strong>@endif</div>
                            </article>
                        @empty<div class="salon-empty panel"><span aria-hidden="true">&#10022;</span><p>This salon has no services listed yet.</p><a href="#contact">Contact the salon for more information &rarr;</a></div>@endforelse
                    </div>
                </section>
                <section id="gallery" class="salon-section" aria-labelledby="gallery-heading" x-data>
                    <div class="salon-section-heading"><div><p class="luxury-kicker">GET TO KNOW THE SPACE</p><h2 id="gallery-heading">A closer <em>look.</em></h2></div></div>
                    <div class="salon-gallery">
                        @forelse($salon->getMedia('gallery') as $media)
                            <button type="button" data-preview-url="{{ \App\Support\PublicUrl::image($media->getUrl()) ?: '/images/salon-placeholder.svg' }}" data-preview-alt="{{ $salon->name }} gallery image {{ $loop->iteration }}" x-on:click="$refs.galleryImage.src = $el.dataset.previewUrl; $refs.galleryImage.alt = $el.dataset.previewAlt; $refs.galleryDialog.showModal()" aria-haspopup="dialog"><x-storefront.image :src="$media->hasGeneratedConversion('preview') ? $media->getUrl('preview') : $media->getUrl()" alt="{{ $salon->name }} gallery image {{ $loop->iteration }}" loading="lazy" width="600" height="450" /><span>View photo <span aria-hidden="true">&#128269;</span></span></button>
                        @empty<div class="salon-empty panel"><span aria-hidden="true">&#10022;</span><p>A little more of the salon, coming soon.</p><small>Gallery photos will appear here when added by the salon.</small></div>@endforelse
                    </div>
                    <dialog x-ref="galleryDialog" class="salon-photo-dialog" aria-labelledby="gallery-preview-title" x-on:click.self="$el.close()" x-on:close="$refs.galleryImage.removeAttribute('src')">
                        <div class="salon-photo-dialog-header"><h3 id="gallery-preview-title">Gallery photo</h3><button type="button" autofocus x-on:click="$refs.galleryDialog.close()" aria-label="Close photo preview">Close &times;</button></div>
                        <img x-ref="galleryImage" alt="Salon gallery preview" x-on:error="if ($el.getAttribute('src') && $el.src !== new URL('/images/salon-placeholder.svg', location.href).href) $el.src = '/images/salon-placeholder.svg'">
                    </dialog>
                </section>
                <section id="contact" class="salon-section salon-contact panel" aria-labelledby="contact-heading">
                    <p class="luxury-kicker">COME SAY HELLO</p><h2 id="contact-heading">Plan your <em>visit.</em></h2>
                    <dl><div><dt>FIND US</dt><dd>{{ $salon->location ?: 'Contact the salon for its address.' }}</dd></div><div><dt>OPENING HOURS</dt><dd>@if($salon->open_time && $salon->close_time){{ $salon->open_time->format('H:i') }} &ndash; {{ $salon->close_time->format('H:i') }}@else Contact the salon for opening hours. @endif</dd></div>
                        @if($salon->phone)<div><dt>GIVE US A CALL</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $salon->phone) }}">{{ $salon->phone }}</a></dd></div>@endif
                        @if(filter_var($salon->email, FILTER_VALIDATE_EMAIL))<div><dt>DROP US A NOTE</dt><dd><a href="mailto:{{ $salon->email }}">{{ $salon->email }}</a></dd></div>@endif
                    </dl>
                    <div class="salon-contact-links">@foreach(['facebook_page' => 'Facebook', 'instagram_page' => 'Instagram', 'tiktok_page' => 'TikTok'] as $field => $label)@if($link = \App\Support\PublicUrl::link($salon->{$field}))<a href="{{ $link }}" target="_blank" rel="noopener noreferrer">{{ $label }} &nearr;</a>@endif @endforeach
                        @if($salon->location)<a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($salon->location) }}" target="_blank" rel="noopener noreferrer">Get directions &nearr;</a>@endif
                    </div>
                </section>
            </div>
            <aside class="salon-booking-column" aria-label="Appointment request"><section id="booking" class="salon-booking panel"><div class="salon-booking-decoration" aria-hidden="true">&#10022;</div><livewire:booking-form :salon-id="$salon->id" /></section><p class="salon-booking-footnote"><span aria-hidden="true">&#10003;</span> A request today. A little time for you, soon.</p></aside>
        </div>
        <section id="reviews" class="salon-section salon-reviews" aria-labelledby="reviews-heading">
            <div class="salon-section-heading"><div><p class="luxury-kicker">THE LITTLE THINGS PEOPLE LOVE</p><h2 id="reviews-heading">Real visits.<br><em>Personal experiences.</em></h2></div>@if($salon->ratings_count)<div class="salon-review-summary"><strong>{{ number_format($salon->public_rating ?? 0, 1) }}<span> / 5</span></strong><p>{{ $salon->ratings_count }} customer {{ \Illuminate\Support\Str::plural('review', $salon->ratings_count) }}</p></div>@endif</div>
            <livewire:frontend.salon-actions :salon-id="$salon->id" />
            <div class="salon-review-grid">
                @forelse($reviews as $review)<article class="salon-review panel"><div class="salon-review-top"><span>&#9733; {{ $review->rating }} / 5</span><time datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('d M Y') }}</time></div><p>{{ $review->review ?: 'This customer left a rating.' }}</p><span class="salon-review-author">Booking customer</span></article>@empty<div class="salon-empty panel"><span aria-hidden="true">&#10022;</span><p>Your experience could be the first.</p><small>No reviews yet. Share your experience after an eligible appointment.</small></div>@endforelse
            </div>
            <div class="mt-6">{{ $reviews->fragment('reviews')->links() }}</div>
        </section>
        <div class="salon-back-link"><a href="{{ route('salons') }}">&larr; Keep exploring salons</a><span>A little time for you, wherever you find it.</span></div>
    </div>
</x-storefront.layout>
