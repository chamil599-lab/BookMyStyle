<?php

namespace App\Filament\Admin\Resources\ServicePages;

use App\Filament\Admin\Resources\ServicePages\Pages\CreateServicePage;
use App\Filament\Admin\Resources\ServicePages\Pages\EditServicePage;
use App\Filament\Admin\Resources\ServicePages\Pages\ListServicePages;
use App\Filament\Admin\Resources\ServicePages\Pages\ViewServicePage;
use App\Filament\Admin\Resources\ServicePages\Schemas\ServicePageForm;
use App\Filament\Admin\Resources\ServicePages\Schemas\ServicePageInfolist;
use App\Filament\Admin\Resources\ServicePages\Tables\ServicePagesTable;
use App\Models\ServicePage;
use App\RolesEnum;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class ServicePageResource extends Resource
{
    protected static ?string $model = ServicePage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookOpen;

    protected static string | UnitEnum | null $navigationGroup = 'Content Management';

    public static function form(Schema $schema): Schema
    {
        return ServicePageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ServicePageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ServicePagesTable::configure($table);
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
            'index' => ListServicePages::route('/'),
            'create' => CreateServicePage::route('/create'),
            'view' => ViewServicePage::route('/{record}'),
            'edit' => EditServicePage::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        return $user?->hasRole(RolesEnum::ADMIN->value) ?? false;
    }
}
