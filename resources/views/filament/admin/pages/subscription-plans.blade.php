<x-filament-panels::page>
    <div class="bms-plan-page">
        <a class="underline" href="{{ route('filament.admin.resources.user-subscriptions.index') }}">View subscription history</a>

        @if(session('error'))<p role="alert" class="bms-plan-card p-4 mb-6">{{ session('error') }}</p>@endif
        @if($activeSub)
            <p class="bms-plan-card p-6 mb-6">Your current plan is {{ $activeSub->package?->name ?? 'your approved plan' }}, active until {{ $activeSub->ends_at->format('j M Y, H:i') }}. {{ $activeSub->is_trial ? 'Free trial' : 'Paid subscription' }} &middot; {{ $activeSub->daysRemaining() }} days remaining. Started {{ $activeSub->starts_at->format('j M Y') }}. It stays active while a new request is reviewed.</p>
        @else
            <p class="bms-plan-card p-6 mb-6">You need an active subscription to use salon management. Choose an available plan below to request access.</p>
        @endif
        @if($pendingSub)
            <p role="status" class="bms-plan-card p-6 mb-6">Your {{ $pendingSub->features_snapshot['name'] ?? $pendingSub->package?->name ?? 'subscription' }} request is pending approval. You can update its payment details in subscription history. Wait for review before submitting another request.</p>
        @endif
        <div class="grid gap-6 md:grid-cols-3">
            @forelse($packages as $package)
                <article class="bms-plan-card p-6" data-subscription-package="{{ $package->id }}">
                    @if($package->is_popular)<p class="text-sm font-semibold mb-2">Popular plan</p>@endif
                    <h2 class="text-xl font-semibold">{{ $package->name }}</h2>
                    <p class="my-4 text-xl font-semibold">LKR {{ number_format($package->price, 2) }}</p>
                    <p>{{ $package->duration_days }} days per subscription period</p>
                    <ul class="my-6 space-y-2">
                        <li>{{ $package->max_salons }} salons</li>
                        <li>{{ $package->max_staff }} staff per salon</li>
                        <li>{{ $package->max_services }} services</li>
                        <li>{{ $package->max_bookings_per_month }} booking requests per month</li>
                        @if($package->analytics)<li>Analytics</li>@endif
                        @if($package->calendar)<li>Booking calendar</li>@endif
                        @if($package->priority_support)<li>Priority support</li>@endif
                    </ul>
                    @if($pendingSub)
                        <p>Awaiting review</p>
                    @else
                        <a class="bms-plan-button" href="{{ \App\Filament\Admin\Pages\SubscriptionCheckout::getUrl(['package' => $package->id], panel: 'admin') }}">{{ $activeSub ? ((int) $activeSub->package_id === (int) $package->id ? 'Request renewal' : 'Request plan change') : 'Choose plan' }}</a>
                    @endif
                </article>
            @empty
                <p class="bms-plan-card p-6">No subscription plans are currently available.</p>
            @endforelse
        </div>
        <p class="mt-8">Requests require administrator approval after payment review. Renewals of the same plan begin when the current period ends; approved changes to a different plan begin immediately.</p>
    </div>
</x-filament-panels::page>
