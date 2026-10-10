<x-customer.layout title="Favorite salons">
    <p class="muted mb-6">Your saved salons, ready for your next visit.</p>
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        @forelse($salons as $salon)
            <div><x-storefront.salon-card :salon="$salon" />
                <form method="POST" action="{{ route('customer.favorite.remove', $salon->id) }}" class="panel p-4 border-t-0">@csrf @method('DELETE')<button class="underline text-sm" type="submit">Remove {{ $salon->name }} from favorites</button></form>
            </div>
        @empty<div class="panel p-8"><p>No favorite salons yet.</p><a class="button mt-5" href="{{ route('salons') }}">Browse salons</a></div>@endforelse
    </div>
    {{ $salons->links() }}
</x-customer.layout>
