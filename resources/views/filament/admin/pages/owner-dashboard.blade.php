<x-filament-panels::page>
    @php
        $stats = $this->ownerStats;
    @endphp

    <div class="space-y-6">

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            {{-- Total Earnings --}}
            <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-currency-dollar" class="w-5 h-5 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Earnings</p>
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

        {{-- Booking Calendar --}}
        @livewire(\App\Filament\Admin\Widgets\BookingCalendarWidget::class)

    </div>
</x-filament-panels::page>
