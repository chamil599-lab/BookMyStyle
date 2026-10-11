<?php

namespace App\Filament\Admin\Resources\Bookings;

use App\Filament\Admin\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Admin\Resources\Bookings\Pages\EditBooking;
use App\Filament\Admin\Resources\Bookings\Pages\ListBookings;
use App\Filament\Admin\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Admin\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Admin\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Admin\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|UnitEnum|null $navigationGroup = 'Booking Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $recordTitleAttribute = 'booking';

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        return $user !== null && Gate::forUser($user)->allows('viewAny', Booking::class);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'gray';
    }

    // Filter which bookings are visible
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['service' => fn ($q) => $q->withTrashed(), 'salon']);

        // Admin sees all bookings
        if (auth()->user()->hasRole('admin')) {
            return $query;
        }

        // Owner sees only bookings for salons they created
        return $query->whereIn(
            'salon_id',
            \App\Models\Salon::where('created_by', auth()->id())->pluck('id')
        );
    }

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'view' => ViewBooking::route('/{record}'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }
}
