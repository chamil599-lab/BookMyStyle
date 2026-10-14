<x-storefront.layout :title="$title">
    <section class="shell py-14 md:py-20 public-content-page">
        <div class="public-page-intro">
        <p class="eyebrow mb-5">{{ $mode === 'deals' ? 'A little more beauty' : 'The service collection' }}</p>
        <h1 class="section-title max-w-3xl mb-6">{{ $title }}</h1>
        <p class="muted text-sm leading-7 max-w-xl mb-10">{{ $mode === 'deals' ? 'Explore salon offers and their terms. Confirm availability and pricing with the salon before booking.' : 'From everyday essentials to your big day, discover the services that make you feel your best.' }}</p>
        </div>
        <form method="GET" action="{{ route($mode) }}" class="flex flex-wrap gap-4 items-end mb-10">
            <div><label class="label" for="category">Category</label><select id="category" name="category" class="field"><option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category }}" @selected(request('category') === $category)>{{ $category }}</option>@endforeach</select></div>
            <button type="submit" class="button">Apply filter</button><a href="{{ route($mode) }}" class="text-sm text-[var(--terra)] py-3">Reset</a>
        </form>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($items as $item)
                <article class="panel overflow-hidden public-editorial-card">
                    @if($image = \App\Support\PublicUrl::image($item->image ?: $item->src_url))<x-storefront.image :src="$image" alt="{{ $item->title }}" loading="lazy" width="600" height="400" class="w-full aspect-[3/2] object-cover" />@endif
                    <div class="p-6">
                        <p class="eyebrow mb-3">{{ $mode === 'deals' ? $item->category : $item->category_name }}</p><h2 class="text-lg mb-4">{{ $item->title }}</h2><p class="muted text-sm leading-7 mb-5 whitespace-pre-line">{{ $item->description }}</p>
                        @if($mode === 'deals')
                            <p class="text-sm mb-3">{{ $item->salon }}</p><p class="font-bold mb-3">LKR {{ number_format((float)$item->discounted_price, 2) }} <del class="muted text-xs font-normal ml-2">LKR {{ number_format((float)$item->original_price, 2) }}</del></p>
                            @if($item->badge)<p class="inline-block bg-[var(--cream)] px-3 py-1 text-xs mb-4">{{ $item->badge }}</p>@endif
                            @if($item->valid_until)<p class="muted text-xs mb-4">{{ $item->valid_until->lt(today()) ? 'Offer ended' : 'Valid until' }} {{ $item->valid_until->format('d M Y') }}</p>@endif
                        @else
                            <p class="font-bold mb-4">@if($item->price !== null)LKR {{ number_format((float)$item->price, 2) }}@else Contact salon for pricing @endif <span class="muted text-xs font-normal ml-2">{{ $item->duration }} minutes</span></p>
                        @endif
                        @if(is_array($item->features))
                            <ul class="text-sm muted space-y-2 mb-6">
                                @foreach($item->features as $feature)
                                    @if(is_string($feature))
                                        <li>✓ {{ $feature }}</li>
                                    @endif
                                @endforeach
                            </ul>
                        @endif
                        <a href="{{ route('salons', $mode === 'deals' && $item->salon ? ['q' => $item->salon] : []) }}" class="text-[var(--terra)] text-sm font-bold">Find a salon →</a>
                    </div>
                </article>
            @empty<div class="panel p-10 sm:col-span-2 lg:col-span-3 text-center"><h2 class="text-xl mb-3">{{ $mode === 'deals' ? 'No offers to show yet' : 'No services to show yet' }}</h2><p class="muted">Try another category or browse our salons.</p><a href="{{ route('salons') }}" class="button mt-6">Explore salons</a></div>@endforelse
        </div>
        <div class="mt-10">{{ $items->links() }}</div>
    </section>
</x-storefront.layout>
