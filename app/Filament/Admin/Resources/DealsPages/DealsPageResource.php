<?php

namespace App\Filament\Admin\Resources\DealsPages;

use App\Filament\Admin\Resources\DealsPages\Pages\CreateDealsPage;
use App\Filament\Admin\Resources\DealsPages\Pages\EditDealsPage;
use App\Filament\Admin\Resources\DealsPages\Pages\ListDealsPages;
use App\Filament\Admin\Resources\DealsPages\Pages\ViewDealsPage;
use App\Filament\Admin\Resources\DealsPages\Schemas\DealsPageForm;
use App\Filament\Admin\Resources\DealsPages\Schemas\DealsPageInfolist;
use App\Filament\Admin\Resources\DealsPages\Tables\DealsPagesTable;
use App\Models\DealsPage;
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

class DealsPageResource extends Resource
{
    protected static ?string $model = DealsPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::BookmarkSquare;

    protected static string | UnitEnum | null $navigationGroup = 'Content Management';

    public static function form(Schema $schema): Schema
    {
        return DealsPageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DealsPageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DealsPagesTable::configure($table);
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
            'index' => ListDealsPages::route('/'),
            'create' => CreateDealsPage::route('/create'),
            'view' => ViewDealsPage::route('/{record}'),
            'edit' => EditDealsPage::route('/{record}/edit'),
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
