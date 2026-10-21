<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Salon;
use Filament\Widgets\LineChartWidget;

class RevenueMonthlyChart extends LineChartWidget
{
    protected ?string $heading = 'Monthly Revenue'; // NOT static

    protected ?string $pollingInterval = null;       // NOT static

    public int $year;

    protected function getData(): array
    {
        $query = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->with('service');

        // Admin sees all
        if (! auth()->user()->hasRole('admin')) {
            $query->whereIn(
                'salon_id',
                Salon::where('created_by', auth()->id())->pluck('id')
            );
        }

        $bookings = $query->get();

        $monthlyRevenue = [];

        // Initialize 12 months
        for ($m = 1; $m <= 12; $m++) {
            $monthlyRevenue[$m] = 0;
        }

        $bookings = $bookings->filter(fn ($b) => $b->date->format('Y') == $this->year);

        foreach ($bookings as $booking) {
            $month = (int) $booking->date->format('n');
            $monthlyRevenue[$month] += $booking->service?->price ?? 0;
        }

        return [
            'labels' => array_map(fn ($m) => date('F', mktime(0, 0, 0, $m, 1)), array_keys($monthlyRevenue)),
            'datasets' => [
                [
                    'label' => 'Revenue (LKR)',
                    'data' => array_values($monthlyRevenue),
                    'backgroundColor' => '#2271b1',
                    'borderColor' => '#2271b1',
                ],
            ],
        ];
    }

    public function mount(): void
    {
        $this->year = now()->year; // default to current year
    }

    protected function getFilters(): array
    {
        // Example: last 5 years
        $years = [];
        for ($i = 0; $i < 5; $i++) {
            $years[] = now()->subYears($i)->year;
        }

        return [
            'year' => $this->year, // bind to public property
        ];
    }
}
