<?php

namespace App\Filament\Admin\Resources\TeamMembers\Pages;

use App\Filament\Admin\Resources\TeamMembers\TeamMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTeamMember extends CreateRecord
{
    protected static string $resource = TeamMemberResource::class;
}
