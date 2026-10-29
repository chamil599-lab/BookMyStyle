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

    <div class="bms-dashboard">
        <x-management.welcome eyebrow="BookMyStyle / Administration" heading="Your business, at a glance." description="A clear view of subscriptions, salon owners and booking activity across your platform." />
        <p class="bms-note">Subscription payments retain expired and canceled history; no refund ledger is available. Monthly payments are grouped by subscription start date. Booking values estimate current prices for confirmed appointments.</p>

        <div class="bms-stat-grid">
            <x-management.stat label="Recorded subscription payments" :value="'LKR '.number_format($rev['subscription_revenue'], 2)" icon="heroicon-o-banknotes" description="Recorded payment history" />
            <x-management.stat label="Active Subscriptions" :value="number_format($subs['active'])" icon="heroicon-o-check-badge" description="Current active plans" />
            <x-management.stat label="Pending Approvals" :value="number_format($subs['pending'])" icon="heroicon-o-clock" description="Subscription requests to review" />
            <x-management.stat label="Total Bookings" :value="number_format($rev['total_bookings'])" icon="heroicon-o-calendar-days" description="All booking requests" />
        </div>

        <x-filament::section heading="Owners and site visits">
            <dl class="bms-overview">
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
                                            backgroundColor: isDark ? 'rgba(125,195,242,0.7)' : 'rgba(34,113,177,0.8)',
                                            borderColor: isDark ? '#7dc3f2' : '#2271b1',
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
                                    borderColor: isDark ? '#7dc3f2' : '#2271b1',
                                    backgroundColor: isDark ? 'rgba(125,195,242,0.1)' : 'rgba(34,113,177,0.15)',
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
