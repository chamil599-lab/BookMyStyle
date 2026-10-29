<?php

namespace App\Filament\Admin\Resources\Bookings\Pages;

use App\Filament\Admin\Resources\Bookings\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.admin.resources.bookings.header');
    }

    public function getSubheading(): ?string
    {
        return 'A little organization. A great experience for every client.';
    }

    public function content(Schema $schema): Schema
    {
        $schema = parent::content($schema);

        return $schema->components([
            View::make('filament.admin.resources.bookings.overview'),
            ...array_slice($schema->getComponents(), 1),
        ]);
    }

    public function getBookingOverview(): array
    {
        $totals = BookingResource::getEloquentQuery()->toBase()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COALESCE(SUM(CASE WHEN date = ? AND status IN (?, ?) THEN 1 ELSE 0 END), 0) as today_count', [today()->toDateString(), 'pending', 'approved'])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) as pending_count', ['pending'])
            ->selectRaw('COALESCE(SUM(CASE WHEN status = ? AND date >= ? THEN 1 ELSE 0 END), 0) as upcoming_count', ['approved', today()->toDateString()])
            ->first();

        return [
            ['label' => 'Total bookings', 'value' => (int) $totals->total, 'meta' => 'Across your booking history', 'icon' => 'heroicon-o-calendar-days'],
            ['label' => 'Today’s appointments', 'value' => (int) $totals->today_count, 'meta' => 'Pending and approved today', 'icon' => 'heroicon-o-sun'],
            ['label' => 'Awaiting approval', 'value' => (int) $totals->pending_count, 'meta' => 'Ready for your attention', 'icon' => 'heroicon-o-clock'],
            ['label' => 'Upcoming confirmed', 'value' => (int) $totals->upcoming_count, 'meta' => 'Approved for today and beyond', 'icon' => 'heroicon-o-check-circle'],
        ];
    }

    public function getTabs(): array
    {
        $tabs = ['all' => Tab::make('All bookings')->icon('heroicon-o-squares-2x2')];

        foreach (['pending' => 'Pending', 'approved' => 'Approved', 'completed' => 'Completed', 'rejected' => 'Rejected'] as $status => $label) {
            $tabs[$status] = Tab::make($label)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', $status));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('New booking')->icon('heroicon-o-plus'),
        ];
    }
}
