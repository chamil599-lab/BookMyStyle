<?php

namespace App\Filament\Admin\Resources\SubscriptionPackages;

use App\Filament\Admin\Resources\SubscriptionPackages\Pages\CreateSubscriptionPackage;
use App\Filament\Admin\Resources\SubscriptionPackages\Pages\EditSubscriptionPackage;
use App\Filament\Admin\Resources\SubscriptionPackages\Pages\ListSubscriptionPackages;
use App\Filament\Admin\Resources\SubscriptionPackages\Pages\ViewSubscriptionPackage;
use App\Filament\Admin\Resources\SubscriptionPackages\Schemas\SubscriptionPackageForm;
use App\Filament\Admin\Resources\SubscriptionPackages\Schemas\SubscriptionPackageInfolist;
use App\Filament\Admin\Resources\SubscriptionPackages\Tables\SubscriptionPackagesTable;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\RolesEnum;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionPackageResource extends Resource
{
    protected static ?string $model = SubscriptionPackage::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellAlert;

    protected static ?string $navigationLabel = 'Subscription Packages';

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user?->hasRole(RolesEnum::ADMIN->value) ?? false;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'gray';
    }

    public static function form(Schema $schema): Schema
    {
        return SubscriptionPackageForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SubscriptionPackageInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionPackagesTable::configure($table);
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
            'index' => ListSubscriptionPackages::route('/'),
            'create' => CreateSubscriptionPackage::route('/create'),
            'view' => ViewSubscriptionPackage::route('/{record}'),
            'edit' => EditSubscriptionPackage::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
