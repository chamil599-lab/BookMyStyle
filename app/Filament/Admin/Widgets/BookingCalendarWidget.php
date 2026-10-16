<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Salon;
use App\Support\ManagementAccess;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Guava\Calendar\Filament\Actions\DeleteAction;
use Guava\Calendar\Filament\Actions\EditAction;
use Guava\Calendar\Filament\CalendarWidget;
use Guava\Calendar\ValueObjects\CalendarEvent;
use Guava\Calendar\ValueObjects\EventClickInfo;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class BookingCalendarWidget extends CalendarWidget
{
    public static function canView(): bool
    {
        $user = Filament::auth()->user()?->fresh();

        return ManagementAccess::admin($user)
            || (ManagementAccess::owner($user, true) && $user->activeSubscription?->package?->calendar);
    }

    protected function getEloquentQuery(string $model): Builder
    {
        abort_unless(static::canView() && $model === Booking::class, 403);
        $query = Booking::query()->with(['salon', 'service' => fn ($q) => $q->withTrashed()]);
        if (! auth()->user()->hasRole('admin')) {
            $query->whereIn('salon_id', Salon::where('created_by', auth()->id())->select('id'));
        }

        return $query;
    }

    protected string $calendarView = 'dayGridMonth';

    protected bool $eventClickEnabled = true;

    public function onEventClickJs(array $data = [], ?string $action = null): void
    {
        abort_unless(static::canView(), 403);
        $action ??= data_get($data, 'event.extendedProps.action') ?? 'view';
        abort_unless($action === 'view', 403);
        parent::onEventClickJs($data, 'view');
    }

    public function editAction(): EditAction
    {
        return parent::editAction()->authorize(false)->visible(false);
    }

    public function deleteAction(): DeleteAction
    {
        return parent::deleteAction()->authorize(false)->visible(false);
    }

    protected function onEventClick(EventClickInfo $info, Model $event, ?string $action = null): void
    {
        abort_unless(static::canView() && $action === 'view', 403);
        parent::onEventClick($info, $event, 'view');
    }

    /** Appointment times are salon wall times, not the browser's timezone. */
    protected function getCalendarOptions(): array
    {
        return [
            'timeZone' => 'local',
        ];
    }

    public function getEventsJs(array $info): array
    {
        abort_unless(static::canView(), 403);
        Validator::make($info, [
            'startStr' => 'required|string|max:64|date', 'endStr' => 'required|string|max:64|date',
            'tzOffset' => 'nullable|integer|between:-840,840',
        ])->validate();
        $range = new FetchInfo(['startStr' => Carbon::parse($info['startStr'])->toDateString(),
            'endStr' => Carbon::parse($info['endStr'])->toDateString()]);

        return $this->getEvents($range)->map(function (CalendarEvent $event) {
            $start = $event->getStart()->format('Y-m-d\TH:i:s');
            $end = $event->getEnd()->format('Y-m-d\TH:i:s');
            $data = $event->toCalendarObject(0, true);
            $data['start'] = $start;
            $data['end'] = $end;
            $data['editable'] = $data['startEditable'] = $data['durationEditable'] = false;

            return $data;
        })->all();
    }

    /**
     * Fetch booking events
     */
    public function getEvents(FetchInfo $info): Collection|array
    {
        $user = auth()->user();

        $query = $this->getEloquentQuery(Booking::class)->with(['salon', 'service' => fn ($query) => $query->withTrashed()]);
        abort_unless($info->end->greaterThan($info->start) && $info->start->diffInDays($info->end) <= 93, 422);
        $query->whereDate('date', '>=', $info->start->toDateString())->whereDate('date', '<', $info->end->toDateString());

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

            $end = $start->copy()->addMinutes(max(30, (int) ceil(((int) ($booking->service?->duration ?? 30)) / 30) * 30));

            return CalendarEvent::make($booking)
                ->title($booking->name.' - '.($booking->service?->title ?? 'N/A'))
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
            'completed' => '#3b82f6',
            'pending' => '#f59e0b',
            'rejected' => '#ef4444',
            default => '#6b7280',
        };
    }

    /**
     * Popup details
     */
    public function getInfolistSchemaForModel(Schema $schema, ?string $model = null): Schema
    {
        abort_unless(static::canView() && $model === Booking::class, 403);

        return $schema->components([
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
                    'pending' => 'warning',
                    'rejected' => 'danger',
                    default => 'gray',
                }),
        ]);
    }
}
