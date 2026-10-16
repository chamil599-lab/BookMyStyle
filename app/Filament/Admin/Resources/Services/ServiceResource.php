<?php

namespace App\Filament\Admin\Resources\Services;

use App\Filament\Admin\Resources\Services\Pages\CreateService;
use App\Filament\Admin\Resources\Services\Pages\EditService;
use App\Filament\Admin\Resources\Services\Pages\ListServices;
use App\Filament\Admin\Resources\Services\Pages\ViewService;
use App\Filament\Admin\Resources\Services\Schemas\ServiceForm;
use App\Filament\Admin\Resources\Services\Schemas\ServiceInfolist;
use App\Filament\Admin\Resources\Services\Tables\ServicesTable;
use App\Models\Service;
use App\Support\ManagementAccess;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static string|UnitEnum|null $navigationGroup = 'Salon Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScissors;

    protected static ?string $recordTitleAttribute = 'service';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Filament::auth()->user();
        if (ManagementAccess::admin($user)) {
            return $query;
        }

        return $query->where('created_by', $user?->id ?? 0);
    }

    public static function form(Schema $schema): Schema
    {
        return ServiceForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServiceInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicesTable::configure($table);
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
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'view' => ViewService::route('/{record}'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditService::class,
        ]);
    }
}
