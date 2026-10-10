<?php

namespace App\Filament\Admin\Resources\Salons;

use App\Filament\Admin\Resources\Salons\Pages\CreateSalon;
use App\Filament\Admin\Resources\Salons\Pages\EditSalon;
use App\Filament\Admin\Resources\Salons\Pages\ListSalons;
use App\Filament\Admin\Resources\Salons\Pages\ViewSalon;
use App\Filament\Admin\Resources\Salons\Schemas\SalonForm;
use App\Filament\Admin\Resources\Salons\Tables\SalonsTable;
use App\Models\Salon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SalonResource extends Resource
{
    protected static ?string $model = Salon::class;

    protected static string | UnitEnum | null $navigationGroup = 'Salon Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'gray';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('owner')) {
            return $query->where('created_by', auth()->id());
        }

        return $query;
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = auth()->id();
        return $data;
    }

    public static function form(Schema $schema): Schema
    {
        return SalonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SalonsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSalons::route('/'),
            'create' => CreateSalon::route('/create'),
            'view' => ViewSalon::route('/{record}'),
            'edit' => EditSalon::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->hasAnyRole(['admin', 'owner']);
    }
}