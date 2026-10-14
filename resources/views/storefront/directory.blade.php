<x-storefront.layout :title="$title">
    @if ($mode === 'home')
        @include('storefront.home-hero')
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
    @if ($mode === 'home')
        @include('storefront.home-story')
    @endif
</x-storefront.layout>
