<x-storefront.layout :title="$title" :description="\Illuminate\Support\Str::limit(strip_tags($salon->description ?? 'Explore services and book your appointment at '.$salon->name), 155)">
    <section class="shell py-10">
        <a href="{{ route('salons') }}" class="eyebrow">← All salons</a>
        <div class="grid gap-8 lg:grid-cols-2 mt-7 items-center">
            <img src="{{ \App\Support\PublicUrl::image($salon->cover_url) }}" alt="{{ $salon->name }}" class="w-full aspect-[4/3] object-cover" width="800" height="600">
            <div>
                <p class="eyebrow mb-5">{{ $salon->location ?: 'Sri Lanka' }}</p>
                <h1 class="section-title mb-6">{{ $salon->name }}</h1>
                <p class="mb-6 text-sm">★ {{ number_format($salon->public_rating ?? 0, 1) }} <a href="#reviews" class="muted">({{ $salon->ratings_count }} {{ \Illuminate\Support\Str::plural('review', $salon->ratings_count) }})</a></p>
                <p class="muted leading-7 whitespace-pre-line">{{ $salon->description }}</p>
                <a href="#booking" class="button mt-8">Request appointment ↗</a>
            </div>
        </div>
        <nav aria-label="Salon sections" class="flex flex-wrap gap-6 py-6 mt-8 border-y border-[var(--line)] text-sm font-semibold"><a href="#services">Services</a><a href="#booking">Book now</a><a href="#gallery">Gallery</a><a href="#contact">Contact & hours</a><a href="#reviews">Reviews</a></nav>
        <div class="grid gap-10 mt-12 lg:grid-cols-[1.25fr_1fr] items-start">
            <div>
                <section id="services" x-data="{ category: 'all' }">
                    <p class="eyebrow mb-3">Made for you</p><h2 class="text-2xl mb-6">Our services</h2>
                    <label for="service-category" class="label">Filter services</label>
                    <select id="service-category" x-model="category" class="field mb-6"><option value="all">All services</option>@foreach($salon->services->pluck('department')->filter()->unique('id') as $department)<option value="{{ $department->id }}">{{ $department->name }}</option>@endforeach</select>
                    <div class="grid gap-4">
                        @forelse($salon->services as $service)
                            <article class="panel p-6" x-show="category === 'all' || category === '{{ $service->department_id }}'">
                                <p class="eyebrow mb-3">{{ $service->category?->name ?? $service->department?->name ?? 'General' }}</p><h3 class="text-base mb-3">{{ $service->title }}</h3>
                                <p class="muted text-sm leading-6 mb-4">{{ $service->description }}</p>
                                <div class="flex justify-between text-sm"><span>{{ $service->duration ?? 30 }} minutes</span><span>@if($salon->show_price) LKR {{ number_format((float) $service->price, 2) }} @else Contact salon for pricing @endif</span></div>
                            </article>
                        @empty<p class="muted">This salon has no services listed yet.</p>@endforelse
                    </div>
                </section>
                <section id="gallery" class="mt-12">
                    <h2 class="text-2xl mb-6">A closer look</h2>
                    <div class="grid grid-cols-2 gap-4">
                        @forelse($salon->getMedia('gallery') as $media)
                            <a href="{{ \App\Support\PublicUrl::image($media->getUrl()) }}" target="_blank" rel="noopener noreferrer"><img src="{{ \App\Support\PublicUrl::image($media->hasGeneratedConversion('preview') ? $media->getUrl('preview') : $media->getUrl()) }}" alt="{{ $salon->name }} gallery image {{ $loop->iteration }}" loading="lazy" width="400" height="300" class="w-full aspect-[4/3] object-cover"></a>
                        @empty<p class="muted col-span-2">Gallery photos will appear here when added by the salon.</p>@endforelse
                    </div>
                </section>
                <section id="contact" class="mt-12 panel p-6">
                    <h2 class="text-xl mb-6">Contact & opening hours</h2>
                    <dl class="grid gap-5 text-sm"><div><dt class="muted mb-1">Address</dt><dd>{{ $salon->location ?: 'Contact the salon for its address.' }}</dd></div><div><dt class="muted mb-1">Hours</dt><dd>{{ $salon->open_time?->format('H:i') ?? '09:00' }} – {{ $salon->close_time?->format('H:i') ?? '18:00' }}</dd></div>
                        @if($salon->phone)<div><dt class="muted mb-1">Phone</dt><dd><a href="tel:{{ preg_replace('/[^0-9+]/', '', $salon->phone) }}">{{ $salon->phone }}</a></dd></div>@endif
                        @if(filter_var($salon->email, FILTER_VALIDATE_EMAIL))<div><dt class="muted mb-1">Email</dt><dd><a href="mailto:{{ $salon->email }}">{{ $salon->email }}</a></dd></div>@endif
                    </dl>
                    <div class="flex flex-wrap gap-5 mt-6 text-[var(--terra)] text-sm">
                        @foreach(['facebook_page' => 'Facebook', 'instagram_page' => 'Instagram', 'tiktok_page' => 'TikTok'] as $field => $label)
                            @if($link = \App\Support\PublicUrl::link($salon->{$field}))<a href="{{ $link }}" target="_blank" rel="noopener noreferrer">{{ $label }} ↗</a>@endif
                        @endforeach
                        @if($salon->location)<a href="https://www.google.com/maps/search/?api=1&query={{ rawurlencode($salon->location) }}" target="_blank" rel="noopener noreferrer">View map ↗</a>@endif
                    </div>
                </section>
            </div>
            <section id="booking" class="panel p-6 md:p-8 lg:sticky lg:top-28"><livewire:booking-form :salon-id="$salon->id" /></section>
        </div>
        <section id="reviews" class="mt-14">
            <p class="eyebrow mb-3">Customer experiences</p><h2 class="text-2xl mb-6">Reviews</h2>
            <livewire:frontend.salon-actions :salon-id="$salon->id" />
            <div class="grid gap-4 mt-8 md:grid-cols-2">
                @forelse($reviews as $review)<article class="panel p-6"><div class="flex justify-between gap-4 mb-4"><span>★ {{ $review->rating }} / 5</span><time class="muted text-xs" datetime="{{ $review->created_at->toDateString() }}">{{ $review->created_at->format('d M Y') }}</time></div><p class="text-sm leading-7 whitespace-pre-line">{{ $review->review ?: 'This customer left a rating.' }}</p><p class="muted text-xs mt-4">Booking customer</p></article>@empty<p class="muted">No reviews yet. Be the first to share your experience after your appointment is approved.</p>@endforelse
            </div>
            <div class="mt-6">{{ $reviews->fragment('reviews')->links() }}</div>
        </section>
    </section>
</x-storefront.layout>
