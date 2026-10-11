<x-filament-panels::page>
    @php
        $stats = $this->ownerStats;
    @endphp

    <div class="space-y-6">
        <p class="text-sm">Booking value uses current service prices, including future confirmed appointments. It does not represent payments collected.</p>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Confirmed booking value (estimate) --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-currency-dollar" class="w-5 h-5 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Confirmed booking value (estimate)</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">LKR {{ number_format($stats['total_earnings'], 2) }}</p>
                    </div>
                </div>
            </div>

            {{-- Total Bookings --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Bookings</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($stats['total_bookings']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <x-filament::section heading="Account overview">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div><dt>Salons</dt><dd>{{ $stats['salons'] }}</dd></div>
                <div><dt>Pending bookings</dt><dd>{{ $stats['pending'] }}</dd></div>
                <div><dt>Completed bookings</dt><dd>{{ $stats['completed'] }}</dd></div>
            </dl>
        </x-filament::section>
        <x-filament::section heading="Subscription">
            @if($subscription = $stats['subscription'])
                <p>{{ $subscription->package?->name ?? 'Archived plan' }}{{ $subscription->is_trial ? ' (trial)' : '' }}</p>
                <p>Ends {{ $subscription->ends_at->format('d M Y') }} ? {{ $subscription->daysRemaining() }} days remaining.</p>
                <p>Monthly booking requests: {{ $stats['month_bookings'] }} / {{ $subscription->package?->max_bookings_per_month ?? 'Unlimited' }} across all salons.</p>
                @if(\App\Filament\Admin\Pages\AnalyticsDashboard::canAccess())
                    <a class="underline" href="{{ \App\Filament\Admin\Pages\AnalyticsDashboard::getUrl() }}">View analytics</a>
                @endif
            @else
                <p>No current active subscription.</p>
            @endif
            <a class="underline" href="{{ route('subscription.required') }}">View subscription options</a>
        </x-filament::section>
        {{-- Booking Calendar --}}
        @if(\App\Filament\Admin\Widgets\BookingCalendarWidget::canView())
            <p class="text-sm">Appointments use salon time ({{ config('app.timezone') }}). Select an event to view details; change appointments from Bookings.</p>
            @livewire(\App\Filament\Admin\Widgets\BookingCalendarWidget::class)
        @else
            <p>Your plan does not include the booking calendar. <a class="underline" href="{{ route('subscription.required') }}">View subscription options</a></p>
        @endif

    </div>
</x-filament-panels::page>
