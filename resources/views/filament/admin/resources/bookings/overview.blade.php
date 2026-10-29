<div class="bms-bookings-overview">
    <div class="bms-stat-grid" aria-label="Booking totals">
        @foreach ($this->getBookingOverview() as $stat)
            <div class="bms-stat">
                <div class="bms-stat-top">
                    <p class="bms-stat-label">{{ $stat['label'] }}</p>
                    <span class="bms-stat-icon"><x-filament::icon :icon="$stat['icon']" /></span>
                </div>
                <p class="bms-stat-value">{{ number_format($stat['value']) }}</p>
                <p class="bms-stat-meta">{{ $stat['meta'] }}</p>
            </div>
        @endforeach
    </div>
</div>
