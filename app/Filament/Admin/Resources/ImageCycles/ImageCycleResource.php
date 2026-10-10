<?php

namespace App\Filament\Admin\Resources\ImageCycles;

use App\Filament\Admin\Resources\ImageCycles\Pages\CreateImageCycle;
use App\Filament\Admin\Resources\ImageCycles\Pages\EditImageCycle;
use App\Filament\Admin\Resources\ImageCycles\Pages\ListImageCycles;
use App\Filament\Admin\Resources\ImageCycles\Pages\ViewImageCycle;
use App\Filament\Admin\Resources\ImageCycles\Schemas\ImageCycleForm;
use App\Filament\Admin\Resources\ImageCycles\Schemas\ImageCycleInfolist;
use App\Filament\Admin\Resources\ImageCycles\Tables\ImageCyclesTable;
use App\Models\ImageCycle;
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

class ImageCycleResource extends Resource
{
    protected static ?string $model = ImageCycle::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::CircleStack;

    protected static string | UnitEnum | null $navigationGroup = 'Content Management';
    
    public static function form(Schema $schema): Schema
    {
        return ImageCycleForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ImageCycleInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ImageCyclesTable::configure($table);
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
            'index' => ListImageCycles::route('/'),
            'create' => CreateImageCycle::route('/create'),
            'view' => ViewImageCycle::route('/{record}'),
            'edit' => EditImageCycle::route('/{record}/edit'),
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
