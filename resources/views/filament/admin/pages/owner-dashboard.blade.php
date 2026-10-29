<x-filament-panels::page>
    @php
        $stats = $this->ownerStats;
    @endphp

    <div class="bms-dashboard">
        <x-management.welcome eyebrow="BookMyStyle / Your workspace" heading="Make room for a beautiful day." description="Your salons, appointments and membership, brought together in one place." />
        <p class="bms-note">Booking value uses current service prices, including future confirmed appointments. It does not represent payments collected.</p>

        <div class="bms-stat-grid">
            <x-management.stat label="Confirmed booking value (estimate)" :value="'LKR '.number_format($stats['total_earnings'], 2)" icon="heroicon-o-banknotes" description="Current service prices" />
            <x-management.stat label="Total Bookings" :value="number_format($stats['total_bookings'])" icon="heroicon-o-calendar-days" description="Across your salons" />
            <x-management.stat label="Pending bookings" :value="number_format($stats['pending'])" icon="heroicon-o-clock" description="Awaiting your response" />
            <x-management.stat label="Completed bookings" :value="number_format($stats['completed'])" icon="heroicon-o-check-badge" description="Completed appointments" />
        </div>

        <x-filament::section heading="Account overview">
            <dl class="bms-overview">
                <div><dt>Salons</dt><dd>{{ $stats['salons'] }}</dd></div>
                <div><dt>Monthly booking requests</dt><dd>{{ number_format($stats['month_bookings']) }}</dd></div>
                <div><dt>Membership days remaining</dt><dd>{{ $stats['subscription']?->daysRemaining() ?? 'No active plan' }}</dd></div>
            </dl>
        </x-filament::section>
        <x-filament::section heading="Your membership">
            <div class="bms-subscription">
                <div>
                    @if($subscription = $stats['subscription'])
                        <p class="bms-eyebrow">Current plan</p>
                        <h3>{{ $subscription->package?->name ?? 'Archived plan' }}{{ $subscription->is_trial ? ' (trial)' : '' }}</h3>
                        <p>Ends {{ $subscription->ends_at->format('d M Y') }} &middot; {{ $subscription->daysRemaining() }} days remaining.</p>
                        <p>Monthly booking requests: {{ $stats['month_bookings'] }} / {{ $subscription->package?->max_bookings_per_month ?? 'Unlimited' }} across all salons.</p>
                    @else
                        <h3>Find the right plan for your salon.</h3>
                        <p>No current active subscription.</p>
                    @endif
                </div>
                <div class="bms-actions">
                    @if(\App\Filament\Admin\Pages\AnalyticsDashboard::canAccess())
                        <x-filament::button color="gray" tag="a" :href="\App\Filament\Admin\Pages\AnalyticsDashboard::getUrl()" icon="heroicon-o-chart-bar">View analytics</x-filament::button>
                    @endif
                    <x-filament::button tag="a" :href="\App\Filament\Admin\Pages\SubscriptionPlans::getUrl(panel: 'admin')" icon="heroicon-o-arrow-up-right">View subscription options</x-filament::button>
                </div>
            </div>
        </x-filament::section>
        {{-- Booking Calendar --}}
        @if(\App\Filament\Admin\Widgets\BookingCalendarWidget::canView())
            <p class="text-sm">Appointments use salon time ({{ config('app.timezone') }}). Select an event to view details; change appointments from Bookings.</p>
            @livewire(\App\Filament\Admin\Widgets\BookingCalendarWidget::class)
        @else
            <p>Your plan does not include the booking calendar. <a class="underline" href="{{ \App\Filament\Admin\Pages\SubscriptionPlans::getUrl(panel: 'admin') }}">View subscription options</a></p>
        @endif

    </div>
</x-filament-panels::page>
