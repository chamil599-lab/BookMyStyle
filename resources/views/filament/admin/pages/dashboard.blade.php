<x-filament-panels::page>
    @php
        $subs = $this->subscriptionStats;
        $rev = $this->revenueStats;
        $monthlySub = $this->monthlySubRevenue;
        $subStatus = $this->subStatusData;
        $dailyBooking = $this->dailyBookingRevenue;
        $topPkgs = $this->topPackages;
        $overview = $this->overview;
    @endphp

    @assets
    @vite('resources/js/management-charts.js')
    @endassets

    <div class="space-y-6">
        <p class="text-sm">Subscription payments retain expired and canceled history; no refund ledger is available. Monthly payments are grouped by subscription start date. Booking values estimate current prices for confirmed appointments.</p>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-success-100 dark:bg-success-900/30 flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-currency-dollar" class="w-5 h-5 text-success-600 dark:text-success-400" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Recorded subscription payments</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">LKR {{ number_format($rev['subscription_revenue'], 2) }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-check-badge" class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Active Subscriptions</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $subs['active'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-warning-100 dark:bg-warning-900/30 flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-clock" class="w-5 h-5 text-warning-600 dark:text-warning-400" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Pending Approvals</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $subs['pending'] }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-5">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-info-100 dark:bg-info-900/30 flex items-center justify-center">
                        <x-filament::icon icon="heroicon-o-calendar-days" class="w-5 h-5 text-info-600 dark:text-info-400" />
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Bookings</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $rev['total_bookings'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <x-filament::section heading="Owners and site visits">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                @foreach(['owners' => 'Owners', 'pending_owners' => 'Owners awaiting approval', 'visits' => 'Total visits', 'today_visits' => 'Visits today', 'month_visits' => 'Visits this month', 'unique_visitors' => 'Unique visitor identifiers'] as $key => $label)
                    <div><dt>{{ $label }}</dt><dd>{{ number_format($overview[$key]) }}</dd></div>
                @endforeach
            </dl>
        </x-filament::section>
        {{-- Charts Row --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Payments by subscription start month --}}
            <div class="lg:col-span-2">
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-chart-bar" class="w-5 h-5" />
                            Payments by subscription start month
                        </div>
                    </x-slot>

                    <div
                        class="h-72"
                        x-data="{
                            init() { this.$nextTick(() => this.waitAndRender()); },
                            waitAndRender() {
                                if (typeof Chart === 'undefined') { setTimeout(() => this.waitAndRender(), 100); return; }
                                this.renderChart();
                            },
                            renderChart() {
                                if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); }
                                const labels = JSON.parse(this.$el.dataset.labels);
                                const values = JSON.parse(this.$el.dataset.values);
                                const isDark = document.documentElement.classList.contains('dark');
                                new Chart(this.$refs.canvas, {
                                    type: 'bar',
                                    data: {
                                        labels: labels,
                                        datasets: [{
                                            label: 'Revenue (LKR)',
                                            data: values,
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
                        data-labels='@json($monthlySub["labels"])'
                        data-values='@json($monthlySub["data"])'
                    >
                        <canvas x-ref="canvas"></canvas>
                    </div>
                </x-filament::section>
            </div>

            {{-- Subscription Status Breakdown --}}
            <div>
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon="heroicon-o-chart-pie" class="w-5 h-5" />
                            Subscription Status
                        </div>
                    </x-slot>

                    <div class="space-y-4">
                        @php $subTotal = max($subs['total'], 1); @endphp
                        @foreach([
                            ['label' => 'Active', 'count' => $subs['active'], 'color' => 'success'],
                            ['label' => 'Scheduled', 'count' => $subs['scheduled'], 'color' => 'info'],
                            ['label' => 'Rejected', 'count' => $subs['rejected'], 'color' => 'gray'],
                            ['label' => 'Pending', 'count' => $subs['pending'], 'color' => 'warning'],
                            ['label' => 'Expired', 'count' => $subs['expired'], 'color' => 'danger'],
                            ['label' => 'Canceled', 'count' => $subs['canceled'], 'color' => 'gray'],
                        ] as $item)
                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $item['label'] }}</span>
                                    <span class="text-sm font-semibold text-{{ $item['color'] }}-600 dark:text-{{ $item['color'] }}-400">{{ $item['count'] }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                    <div class="bg-{{ $item['color'] }}-500 h-2.5 rounded-full" style="width: {{ round(($item['count'] / $subTotal) * 100) }}%"></div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Donut --}}
                        <div class="pt-2 flex justify-center">
                            <div
                                class="w-40 h-40"
                                x-data="{
                                    init() { this.$nextTick(() => this.waitAndRender()); },
                                    waitAndRender() {
                                        if (typeof Chart === 'undefined') { setTimeout(() => this.waitAndRender(), 100); return; }
                                        this.renderChart();
                                    },
                                    renderChart() {
                                        if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); }
                                        const values = JSON.parse(this.$el.dataset.values);
                                        const isDark = document.documentElement.classList.contains('dark');
                                        new Chart(this.$refs.canvas, {
                                            type: 'doughnut',
                                            data: {
                                                labels: ['Active', 'Scheduled', 'Pending', 'Expired', 'Canceled', 'Rejected'],
                                                datasets: [{ data: values, backgroundColor: ['#22c55e', '#3b82f6', '#f59e0b', '#ef4444', '#6b7280', '#a855f7'], borderWidth: 0 }]
                                            },
                                            options: {
                                                responsive: true,
                                                maintainAspectRatio: true,
                                                cutout: '65%',
                                                plugins: {
                                                    legend: { position: 'bottom', labels: { color: isDark ? '#9ca3af' : '#6b7280', padding: 10, font: { size: 11 } } }
                                                }
                                            }
                                        });
                                    },
                                    destroy() { if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); } }
                                }"
                                data-values='@json($subStatus)'
                            >
                                <canvas x-ref="canvas"></canvas>
                            </div>
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>

        {{-- Booking Revenue Trend --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-arrow-trending-up" class="w-5 h-5" />
                    Estimated booking value (Last 30 Days)
                </div>
            </x-slot>

            <div
                class="h-64"
                x-data="{
                    init() { this.$nextTick(() => this.waitAndRender()); },
                    waitAndRender() {
                        if (typeof Chart === 'undefined') { setTimeout(() => this.waitAndRender(), 100); return; }
                        this.renderChart();
                    },
                    renderChart() {
                        if (typeof Chart !== 'undefined') { Chart.getChart(this.$refs.canvas)?.destroy(); }
                        const labels = JSON.parse(this.$el.dataset.labels);
                        const values = JSON.parse(this.$el.dataset.values);
                        const isDark = document.documentElement.classList.contains('dark');
                        new Chart(this.$refs.canvas, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [{
                                    label: 'Revenue (LKR)',
                                    data: values,
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
                data-labels='@json($dailyBooking["labels"])'
                data-values='@json($dailyBooking["data"])'
            >
                <canvas x-ref="canvas"></canvas>
            </div>
        </x-filament::section>

        {{-- Top Packages --}}
        <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-trophy" class="w-5 h-5" />
                        Top Subscription Packages
                    </div>
                </x-slot>

                @if($topPkgs->isEmpty())
                    <p class="text-sm text-gray-400 dark:text-gray-500 text-center py-4">No active subscriptions yet.</p>
                @else
                    <div class="space-y-3">
                        @foreach($topPkgs as $index => $pkg)
                            <div class="flex items-center justify-between py-2.5 {{ !$loop->last ? 'border-b border-gray-200 dark:border-gray-700' : '' }}">
                                <div class="flex items-center gap-3">
                                    <span @class([
                                        'w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold',
                                        'bg-primary-100 text-primary-700 dark:bg-primary-900/30 dark:text-primary-400' => $index === 0,
                                        'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => $index > 0,
                                    ])>{{ $index + 1 }}</span>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $pkg['name'] }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pkg['count'] }} subscribers</p>
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-gray-900 dark:text-white">LKR {{ number_format($pkg['revenue'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-filament::section>
    </div>
</x-filament-panels::page>
