<?php

namespace App\Filament\Admin\Resources\UserSubscriptions;

use App\Filament\Admin\Resources\UserSubscriptions\Pages\CreateUserSubscription;
use App\Filament\Admin\Resources\UserSubscriptions\Pages\EditUserSubscription;
use App\Filament\Admin\Resources\UserSubscriptions\Pages\ListUserSubscriptions;
use App\Filament\Admin\Resources\UserSubscriptions\Pages\ViewUserSubscription;
use App\Filament\Admin\Resources\UserSubscriptions\Schemas\UserSubscriptionForm;
use App\Filament\Admin\Resources\UserSubscriptions\Schemas\UserSubscriptionInfolist;
use App\Filament\Admin\Resources\UserSubscriptions\Tables\UserSubscriptionsTable;
use App\Models\UserSubscription;
use App\RolesEnum;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserSubscriptionResource extends Resource
{
    protected static ?string $model = UserSubscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBellSnooze;

    protected static string | \UnitEnum | null $navigationGroup = 'Billing';

    protected static ?string $navigationLabel = 'User Subscriptions';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function form(Schema $schema): Schema
    {
        return UserSubscriptionForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserSubscriptionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserSubscriptionsTable::configure($table);
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
            'index' => ListUserSubscriptions::route('/'),
            'create' => CreateUserSubscription::route('/create'),
            'view' => ViewUserSubscription::route('/{record}'),
            'edit' => EditUserSubscription::route('/{record}/edit'),
        ];
    }

    /**
     * Filter records based on user role
     * Admin sees all, Owner sees only their subscriptions
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();

        // Admin sees all subscriptions
        if ($user?->hasRole(RolesEnum::ADMIN->value)) {
            return $query;
        }

        // Owner sees only their own subscriptions
        return $query->where('user_id', $user->id);
    }

    /**
     * Only admin can view all subscriptions
     * Owner can only see their own
     */
    public static function canViewAny(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        return $user?->hasRole([RolesEnum::ADMIN->value, RolesEnum::OWNER->value]) ?? false;
    }

    /**
     * Only admin can create subscriptions
     */
    public static function canCreate(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        return $user?->hasRole(RolesEnum::ADMIN->value) ?? false;
    }

    /**
     * Only admin can edit subscriptions
     */
    public static function canEdit($record): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        return $user?->hasRole(RolesEnum::ADMIN->value) ?? false;
    }

    /**
     * No one can delete subscriptions
     */
    public static function canDelete($record): bool
    {
        return false;
    }

    /**
     * No one can bulk-delete subscriptions
     */
    public static function canDeleteAny(): bool
    {
        return false;
    }
}
