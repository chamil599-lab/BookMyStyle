<x-filament-panels::page>
    @php
        $revenue = $this->revenueStats;
        $bookings = $this->bookingStats;
        $monthly = $this->monthlyRevenueData;
        $daily = $this->dailyRevenueData;
        $topServices = $this->topServices;
        $filteredProfit = $this->filteredProfit;
        $profitYears = $this->profitYears;
        $avgRating = $this->averageRating;
        $salons = $this->ownerSalons;
        $bookingTotal = $bookings['total'] ?: 1;
    @endphp

    {{-- Load Chart.js --}}
    @assets
    @vite('resources/js/management-charts.js')
    @endassets

    <div class="space-y-6">
        <p class="text-sm">Values are estimates using current service prices, not payments or profit. Period charts include appointments through today; all-time and selected-month totals include future confirmed appointments.</p>

        @foreach(['period', 'salonId', 'profitYear', 'profitMonth'] as $field)
            @error($field)<p class="text-danger-600" role="alert">{{ $message }}</p>@enderror
        @endforeach
        <p class="text-sm">Completed bookings in this period: {{ $bookings['completed'] }}. Monthly usage counts requests created across all your salons.</p>
        {{-- Filters --}}
        <div
            x-data="{ period: @js($this->period), salonId: @js($this->salonId) }"
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            {{-- Period Filter --}}
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Period:</span>
                @foreach([
                    '7d' => '7 Days',
                    '30d' => '30 Days',
                    '90d' => '90 Days',
                    '12m' => '12 Months',
                    'all' => 'All Time',
                ] as $value => $label)
                    <button
                        type="button"
                        x-on:click="period = '{{ $value }}'; $wire.set('period', '{{ $value }}')"
                        :class="period === '{{ $value }}'
                            ? 'px-3 py-1.5 text-sm font-medium rounded-lg transition-all bg-primary-500 text-gray-500! shadow-sm'
                            : 'px-3 py-1.5 text-sm font-medium rounded-lg transition-all bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700'"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Salon Filter --}}
            @if($salons->count() > 1)
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Salon:</span>
                    <select
                        x-model="salonId"
                        x-on:change="$wire.set('salonId', salonId ? parseInt(salonId) : null)"
                        class="text-sm border-gray-300 rounded-sm shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">All Salons</option>
                        @foreach($salons as $salon)
                            <option value="{{ $salon->id }}">{{ $salon->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- All confirmed booking value --}}
            <div class="p-5 bg-white border border-gray-200 rounded-xl dark:bg-gray-900 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-success-100 dark:bg-success-900/30">
                        <x-filament::icon icon="heroicon-o-currency-dollar" class="w-5 h-5 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">All confirmed booking value</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">LKR {{ number_format($revenue['total'], 2) }}</p>
                    </div>
                </div>
            </div>

            {{-- Period booking value --}}
            <div class="p-5 bg-white border border-gray-200 rounded-xl dark:bg-gray-900 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                        <x-filament::icon icon="heroicon-o-arrow-trending-up" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Period booking value</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">LKR {{ number_format($revenue['period'], 2) }}</p>
                    </div>
                </div>
            </div>

            {{-- Total Bookings --}}
            <div class="p-5 bg-white border border-gray-200 rounded-xl dark:bg-gray-900 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-warning-100 dark:bg-warning-900/30">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5 text-warning-600 dark:text-warning-400" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Bookings</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $bookings['total'] }}</p>
                    </div>
                </div>
            </div>

            {{-- Average Rating --}}
            <div class="p-5 bg-white border border-gray-200 rounded-xl dark:bg-gray-900 dark:border-gray-700">
                <div class="flex items-center gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-amber-100 dark:bg-amber-900/30">
                        <x-filament::icon icon="heroicon-o-star" class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Avg Rating</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ number_format($avgRating, 1) }} / 5</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Row --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            {{-- Monthly booking value Chart --}}
            <div class="lg:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="w-5 h-5" />
                            Monthly booking value
                        </div>
                    </x-slot>

                    <div
                        class="h-72"
                        wire:ignore
                        x-data="{
                            init() {
                                this.$nextTick(() => this.renderChart());
                            },
                            renderChart() {
                                if (typeof Chart === 'undefined') {
                                    setTimeout(() => this.renderChart(), 100);
                                    return;
                                }
                                if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); }
                                const isDark = document.documentElement.classList.contains('dark');
                                new Chart(this.$refs.canvas, {
                                    type: 'bar',
                                    data: {
                                        labels: @js($monthly['labels']),
                                        datasets: [{
                                            label: 'Estimated booking value (LKR)',
                                            data: @js($monthly['data']),
                                            backgroundColor: isDark ? 'rgba(34,197,94,0.6)' : 'rgba(34,197,94,0.8)',
                                            borderColor: '#22c55e',
                                            borderWidth: 1,
                                            borderRadius: 4,
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: { legend: { display: false } },
                                        scales: {
                                            y: {
                                                beginAtZero: true,
                                                grid: { color: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)' },
                                                ticks: { color: isDark ? '#9ca3af' : '#6b7280', callback: v => 'LKR ' + v.toLocaleString() }
                                            },
                                            x: {
                                                grid: { display: false },
                                                ticks: { color: isDark ? '#9ca3af' : '#6b7280', maxRotation: 45 }
                                            }
                                        }
                                    }
                                });
                            },
                            destroy() { if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); } }
                        }"
                        wire:key="monthly-chart-{{ $this->period }}-{{ $this->salonId }}"
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </x-filament::section>
            </div>

            {{-- Booking Status Breakdown --}}
            <div>
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-chart-pie" class="w-5 h-5" />
                            Booking Status
                        </div>
                    </x-slot>

                    <div class="space-y-4">
                        {{-- Approved --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Approved</span>
                                <span class="text-sm font-semibold text-success-600 dark:text-success-400">{{ $bookings['approved'] }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="bg-success-500 h-2.5 rounded-full" style="width: {{ round(($bookings['approved'] / $bookingTotal) * 100) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">{{ round(($bookings['approved'] / $bookingTotal) * 100) }}%</p>
                        </div>

                        {{-- Pending --}}
<div>
    <div class="flex items-center justify-between mb-1">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Pending</span>
        <span class="text-sm font-semibold text-warning-600 dark:text-warning-400">
            {{ $bookings['pending'] }}
        </span>
    </div>

    @php
        $pendingPercent = $bookingTotal > 0
            ? round(($bookings['pending'] / $bookingTotal) * 100)
            : 0;
    @endphp

    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
        <div
            class="bg-warning-500 h-2.5 rounded-full transition-all"
            style="width: {{ $pendingPercent }}%">
        </div>
    </div>

    <p class="mt-0.5 text-xs text-gray-400">
        {{ $pendingPercent }}%
    </p>
</div>


                        {{-- Rejected --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Rejected</span>
                                <span class="text-sm font-semibold text-danger-600 dark:text-danger-400">{{ $bookings['rejected'] }}</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                <div class="bg-danger-500 h-2.5 rounded-full" style="width: {{ round(($bookings['rejected'] / $bookingTotal) * 100) }}%"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-0.5">{{ round(($bookings['rejected'] / $bookingTotal) * 100) }}%</p>
                        </div>

                        {{-- Subscription Booking Usage --}}
                        @if($bookings['max_per_month'])
                            @php
                                $usagePercent = $bookings['max_per_month'] > 0
                                    ? min(round(($bookings['current_month'] / $bookings['max_per_month']) * 100), 100)
                                    : 0;
                                $isNearLimit = $usagePercent >= 80;
                                $isAtLimit = $bookings['current_month'] >= $bookings['max_per_month'];
                            @endphp
                            <div class="pt-2 mt-2 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <x-filament::icon icon="heroicon-o-ticket" class="inline w-4 h-4 mr-1 -mt-0.5" />
                                        Monthly Limit
                                    </span>
                                    <span @class([
                                        'text-sm font-semibold',
                                        'text-danger-600 dark:text-danger-400' => $isAtLimit,
                                        'text-warning-600 dark:text-warning-400' => $isNearLimit && !$isAtLimit,
                                        'text-primary-600 dark:text-primary-400' => !$isNearLimit,
                                    ])>
                                        {{ $bookings['current_month'] }} / {{ $bookings['max_per_month'] }}
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                    <div @class([
                                        'h-2.5 rounded-full transition-all',
                                        'bg-danger-500' => $isAtLimit,
                                        'bg-warning-500' => $isNearLimit && !$isAtLimit,
                                        'bg-primary-500' => !$isNearLimit,
                                    ]) style="width: {{ $usagePercent }}%"></div>
                                </div>
                                <p class="text-xs mt-0.5 {{ $isAtLimit ? 'text-danger-500' : 'text-gray-400' }}">
                                    {{ $usagePercent }}% used this month
                                    @if($isAtLimit)
                                        — Limit reached
                                    @endif
                                </p>
                            </div>
                        @endif

                        {{-- Donut Chart --}}
                        <div class="flex justify-center pt-2">
                            <div
                                class="w-40 h-40"
                                wire:ignore
                                x-data="{
                                    init() {
                                        this.$nextTick(() => this.renderChart());
                                    },
                                    renderChart() {
                                        if (typeof Chart === 'undefined') {
                                            setTimeout(() => this.renderChart(), 100);
                                            return;
                                        }
                                        if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); }
                                        const isDark = document.documentElement.classList.contains('dark');
                                        new Chart(this.$refs.canvas, {
                                            type: 'doughnut',
                                            data: {
                                                labels: ['Approved', 'Completed', 'Pending', 'Rejected'],
                                                datasets: [{
                                                    data: [{{ $bookings['approved'] }}, {{ $bookings['completed'] }}, {{ $bookings['pending'] }}, {{ $bookings['rejected'] }}],
                                                    backgroundColor: ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444'],
                                                    borderWidth: 0,
                                                }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: true,
                                                cutout: '65%',
                                                plugins: {
                                                    legend: {
                                                        position: 'bottom',
                                                        labels: { color: isDark ? '#9ca3af' : '#6b7280', padding: 10, font: { size: 11 } }
                                                    }
                                                }
                                            }
                                        });
                                    },
                                    destroy() { if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); } }
                                }"
                                wire:key="donut-chart-{{ $this->period }}-{{ $this->salonId }}"
                            >
                                <canvas x-ref="canvas"></canvas>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        {{-- Daily Revenue Chart --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-arrow-trending-up" class="w-5 h-5" />
                    Booking value trend
                </div>
            </x-slot>

            <div
                class="h-64"
                wire:ignore
                x-data="{
                    init() {
                        this.$nextTick(() => this.renderChart());
                    },
                    renderChart() {
                        if (typeof Chart === 'undefined') {
                            setTimeout(() => this.renderChart(), 100);
                            return;
                        }
                        if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); }
                        const isDark = document.documentElement.classList.contains('dark');
                        new Chart(this.$refs.canvas, {
                            type: 'line',
                            data: {
                                labels: @js($daily['labels']),
                                datasets: [{
                                    label: 'Estimated booking value (LKR)',
                                    data: @js($daily['data']),
                                    borderColor: '#3b82f6',
                                    backgroundColor: isDark ? 'rgba(59,130,246,0.1)' : 'rgba(59,130,246,0.15)',
                                    fill: true,
                                    tension: 0.3,
                                    pointRadius: 2,
                                    pointHoverRadius: 5,
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        grid: { color: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)' },
                                        ticks: { color: isDark ? '#9ca3af' : '#6b7280', callback: v => 'LKR ' + v.toLocaleString() }
                                    },
                                    x: {
                                        grid: { display: false },
                                        ticks: { color: isDark ? '#9ca3af' : '#6b7280', maxTicksLimit: 15, maxRotation: 45 }
                                    }
                                }
                            }
                        });
                    },
                    destroy() { if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); } }
                }"
                wire:key="daily-chart-{{ $this->period }}-{{ $this->salonId }}"
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

            {{-- Top Services --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-scissors" class="w-5 h-5" />
                        Top Services
                    </div>
                </x-slot>

                @if($topServices->isEmpty())
                    <p class="py-4 text-sm text-center text-gray-400 dark:text-gray-500">No bookings yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach($topServices as $index => $service)
                            <div class="flex items-center justify-between py-2.5 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                                <div class="flex items-center gap-3">
                                    <span @class([
                                        'w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold',
                                        'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' => $index === 0,
                                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => $index > 0,
                                    ])>{{ $index + 1 }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $service['name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $service['bookings'] }} bookings</p>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">LKR {{ number_format($service['revenue'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>

            {{-- Monthly booking value --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center justify-between w-full">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-banknotes" class="w-5 h-5" />
                            Monthly booking value
                        </div>
                        <div class="flex items-center gap-2">
                            <select
                                wire:model.live="profitMonth"
                                class="text-xs border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                            >
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}">{{ \Carbon\Carbon::create(2000, $m, 1)->format('M') }}</option>
                                @endforeach
                            </select>
                            <select
                                wire:model.live="profitYear"
                                class="text-xs border-gray-300 rounded-md shadow-sm dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary-500 focus:ring-primary-500"
                            >
                                @foreach($profitYears as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </x-slot>

                <div class="space-y-3">
                    {{-- Monthly All confirmed booking value --}}
                    <div class="flex items-center justify-between py-2.5 bg-success-50 dark:bg-success-950/10 rounded-lg px-3 -mx-3">
                        <div class="flex items-center gap-2.5">
                            <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-success-100 dark:bg-success-900/30">
                                <x-filament::icon icon="heroicon-o-currency-dollar" class="w-4 h-4 text-success-600 dark:text-success-400" />
                            </div>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">All confirmed booking value</span>
                        </div>
                        <span class="text-sm font-bold text-success-600 dark:text-success-400">LKR {{ number_format($filteredProfit['total_revenue'], 2) }}</span>
                    </div>

                    {{-- Revenue by Salon --}}
                    @if($filteredProfit['by_salon']->isNotEmpty())
                        @foreach($filteredProfit['by_salon'] as $salonProfit)
                            <div class="flex items-center justify-between py-2.5 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                        <x-filament::icon icon="heroicon-o-building-storefront" class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $salonProfit['name'] }}</span>
                                        <p class="text-xs text-gray-400">{{ $salonProfit['bookings'] }} bookings</p>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">LKR {{ number_format($salonProfit['revenue'], 2) }}</span>
                            </div>
                        @endforeach
                    @else
                        <p class="py-4 text-sm text-center text-gray-400 dark:text-gray-500">No revenue for this month.</p>
                    @endif
                </div>
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
