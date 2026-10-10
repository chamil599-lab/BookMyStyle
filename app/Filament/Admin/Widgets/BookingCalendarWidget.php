<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Salon;
use Carbon\Carbon;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Support\Collection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;

class BookingCalendarWidget extends CalendarWidget
{
    protected string $calendarView = 'dayGridMonth';

    protected bool $eventClickEnabled = true;

    /**
     * 🔥 CRITICAL FIX
     * Force FullCalendar to render in LOCAL timezone
     * Prevents UTC shifting in calendar grid
     */
    protected function getCalendarOptions(): array
    {
        return [
            'timeZone' => 'local',
        ];
    }

    /**
     * Fetch booking events
     */
    public function getEvents(FetchInfo $info): Collection|array
    {
        $user = auth()->user();

        $query = Booking::query()->with(['salon', 'service']);

        // Non-admin users only see their salons
        if (! $user->hasRole('admin')) {
            $salonIds = Salon::where('created_by', $user->id)->pluck('id');
            $query->whereIn('salon_id', $salonIds);
        }

        return $query->get()->map(function (Booking $booking) {

            // Date is already Carbon (model cast)
            $date = $booking->date;

            // Time is string (HH:mm or HH:mm:ss)
            [$hour, $minute, $second] = array_pad(
                explode(':', $booking->time),
                3,
                0
            );

            // Create LOCAL datetime (NO UTC, NO conversion)
            $start = Carbon::create(
                $date->year,
                $date->month,
                $date->day,
                (int) $hour,
                (int) $minute,
                (int) $second
            );

            $end = $start->copy()->addHour();

            return CalendarEvent::make($booking)
                ->title($booking->name . ' - ' . ($booking->service?->title ?? 'N/A'))
                ->start($start)
                ->end($end)
                ->backgroundColor($this->getStatusColor($booking->status));
        });
    }

    /**
     * Event color by status
     */
    protected function getStatusColor(string $status): string
    {
        return match ($status) {
            'approved' => '#22c55e',
            'pending'  => '#f59e0b',
            'rejected' => '#ef4444',
            default    => '#6b7280',
        };
    }

    /**
     * Popup details
     */
    public function getEventInfolist(): ?Infolist
    {
        return Infolist::make()->schema([
            TextEntry::make('name')->label('Customer Name'),
            TextEntry::make('email')->label('Email'),
            TextEntry::make('phone')->label('Phone'),
            TextEntry::make('salon.name')->label('Salon'),
            TextEntry::make('service.title')->label('Service'),
            TextEntry::make('date')->label('Date')->date(),
            TextEntry::make('time')->label('Time'),
            TextEntry::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'approved' => 'success',
                    'pending'  => 'warning',
                    'rejected' => 'danger',
                    default    => 'gray',
                }),
        ]);
    }
}
