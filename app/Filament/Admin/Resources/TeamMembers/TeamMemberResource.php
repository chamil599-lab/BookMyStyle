<?php

namespace App\Filament\Admin\Resources\TeamMembers;

use App\Filament\Admin\Resources\TeamMembers\Pages\CreateTeamMember;
use App\Filament\Admin\Resources\TeamMembers\Pages\EditTeamMember;
use App\Filament\Admin\Resources\TeamMembers\Pages\ListTeamMembers;
use App\Filament\Admin\Resources\TeamMembers\Pages\ViewTeamMember;
use App\Filament\Admin\Resources\TeamMembers\Schemas\TeamMemberForm;
use App\Filament\Admin\Resources\TeamMembers\Schemas\TeamMemberInfolist;
use App\Filament\Admin\Resources\TeamMembers\Tables\TeamMembersTable;
use App\Models\TeamMember;
use App\Models\User;
use App\RolesEnum;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TeamMemberResource extends Resource
{
    protected static ?string $model = TeamMember::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content Management';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    protected static ?string $navigationLabel = 'Team Members';

    public static function form(Schema $schema): Schema
    {
        return TeamMemberForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TeamMemberInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TeamMembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function canViewAny(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user?->hasRole(RolesEnum::ADMIN->value) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTeamMembers::route('/'),
            'create' => CreateTeamMember::route('/create'),
            'view' => ViewTeamMember::route('/{record}'),
            'edit' => EditTeamMember::route('/{record}/edit'),
        ];
    }
}
