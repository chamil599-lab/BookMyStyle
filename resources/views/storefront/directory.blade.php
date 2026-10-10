<x-storefront.layout :title="$title">
    @if ($mode === 'home')
        <section class="hero">
            @php($heroImages = $images->filter(fn($image) => \App\Support\PublicUrl::image($image->image_url))->values())
            @if ($heroImages->isNotEmpty())
                <div x-data="{ active: 0 }" x-init="if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => active = (active + 1) % {{ $heroImages->count() }}, 6000)">
                    @foreach ($heroImages as $image)
                        <img src="{{ \App\Support\PublicUrl::image($image->image_url) }}" alt="{{ $image->alt ?? '' }}" class="hero-media" x-show="active === {{ $loop->index }}" @if(!$loop->first) x-cloak @endif>
                    @endforeach
                </div>
            @else
                <video class="hero-media" src="{{ asset('Girl_touching_her_hair_202605240819.mp4') }}" poster="https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=1469&q=80" autoplay loop muted playsinline aria-hidden="true" x-data x-init="if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) $el.pause()"></video>
            @endif
            <div class="shell">
                <p class="eyebrow mb-8 text-[#e07b47]">Premium beauty platform · Sri Lanka</p>
                <h1>BOOK<br>YOUR<br><span class="editorial font-normal text-[#f5d7b8]">absolute</span> <span class="text-[#e07b47]">BEST.</span></h1>
                <div class="grid gap-8 mt-10 lg:grid-cols-3 items-end">
                    <p class="text-white/75 text-sm leading-7 max-w-sm">Discover salons and beauty studios across Sri Lanka. Explore services, read customer reviews, and find your next great style.</p>
                    <div class="flex gap-8 border border-white/25 p-5 text-center"><div><strong class="block text-2xl">{{ $stats['salons'] }}</strong><span class="text-xs text-white/70">Salons</span></div><div><strong class="block text-2xl">{{ $stats['bookings'] }}</strong><span class="text-xs text-white/70">Bookings</span></div><div><strong class="block text-2xl">{{ $stats['rating'] }}</strong><span class="text-xs text-white/70">Avg. rating</span></div></div>
                    <div><a href="#salons-section" class="button">Find your salon ↗</a></div>
                </div>
            </div>
        </section>
    @endif
    <section id="salons-section" class="shell py-14 md:py-20">
        <p class="eyebrow mb-5">Our collection</p>
        <h{{ $mode === 'home' ? '2' : '1' }} class="section-title mb-5">{{ $mode === 'home' ? 'Explore our salons' : $title }}</h{{ $mode === 'home' ? '2' : '1' }}>
        <p class="muted text-sm leading-7 mb-10 max-w-xl">@if($mode === 'trending') Ranked by approved and completed bookings created in the last 30 days, then customer rating. @elseif($mode === 'popular') Ranked by all-time approved and completed bookings, then customer rating. @else Find your beauty destination. Search by name or location, filter by department, and choose your next appointment. @endif</p>
        <form method="GET" action="{{ route($mode) }}#salons-section" class="panel p-5 grid gap-4 md:grid-cols-[2fr_1fr_1fr_auto] mb-8 items-end">
            <div><label class="label" for="search">Search salons</label><input id="search" name="q" type="search" maxlength="100" value="{{ $filters['q'] ?? '' }}" placeholder="Name or location" class="field"></div>
            <div><label class="label" for="department">Department</label><select id="department" name="department_id" class="field"><option value="">All departments</option>@foreach($departments as $department)<option value="{{ $department->id }}" @selected(($filters['department_id'] ?? '') == $department->id)>{{ $department->name }}</option>@endforeach</select></div>
            @if(!in_array($mode, ['popular', 'trending']))
                <div><label class="label" for="sort">Sort by</label><select id="sort" name="sort" class="field">@foreach(['rating' => 'Top rated', 'reviews' => 'Most reviews', 'name' => 'Name A–Z', 'newest' => 'Newest'] as $value => $label)<option value="{{ $value }}" @selected(($filters['sort'] ?? 'rating') === $value)>{{ $label }}</option>@endforeach</select></div>
            @endif
            <button class="button" type="submit">Find salons</button>
        </form>
        @if($errors->any())<p role="alert" class="mb-6 text-red-700">{{ $errors->first() }}</p>@endif
        <div class="flex justify-between gap-4 mb-6 text-sm"><p class="muted">{{ $salons->total() }} {{ \Illuminate\Support\Str::plural('salon', $salons->total()) }} found</p><a href="{{ route($mode) }}#salons-section" class="text-[var(--terra)]">Reset filters</a></div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($salons as $salon)<x-storefront.salon-card :salon="$salon" />@empty<div class="panel p-10 sm:col-span-2 lg:col-span-3 text-center"><h2 class="text-xl mb-3">No salons found</h2><p class="muted">Try another name, location, or department.</p><a href="{{ route($mode) }}" class="button mt-6">Reset filters</a></div>@endforelse
        </div>
        <div class="mt-10">{{ $salons->links() }}</div>
    </section>
</x-storefront.layout>
