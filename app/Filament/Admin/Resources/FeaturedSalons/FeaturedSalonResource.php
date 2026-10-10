<?php

namespace App\Filament\Admin\Resources\FeaturedSalons;

use App\Filament\Admin\Resources\FeaturedSalons\Pages\CreateFeaturedSalon;
use App\Filament\Admin\Resources\FeaturedSalons\Pages\EditFeaturedSalon;
use App\Filament\Admin\Resources\FeaturedSalons\Pages\ListFeaturedSalons;
use App\Filament\Admin\Resources\FeaturedSalons\Pages\ViewFeaturedSalon;
use App\Filament\Admin\Resources\FeaturedSalons\Schemas\FeaturedSalonForm;
use App\Filament\Admin\Resources\FeaturedSalons\Tables\FeaturedSalonsTable;
use App\Models\FeaturedSalon;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use App\RolesEnum;
use Filament\Support\Icons\Heroicon;

class FeaturedSalonResource extends Resource
{
    protected static ?string $model = FeaturedSalon::class;

    protected static string | UnitEnum | null $navigationGroup = 'Content Management';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::Star;

    protected static ?string $navigationLabel = 'Featured Salons';

    public static function form(Schema $schema): Schema
    {
        return FeaturedSalonForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeaturedSalonsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        return $user?->hasRole(RolesEnum::ADMIN->value) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeaturedSalons::route('/'),
            'create' => CreateFeaturedSalon::route('/create'),
            'view' => ViewFeaturedSalon::route('/{record}'),
            'edit' => EditFeaturedSalon::route('/{record}/edit'),
        ];
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        if ($user) {
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;
        }
        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        if ($user) {
            $data['updated_by'] = $user->id;
        }
        return $data;
    }
}
