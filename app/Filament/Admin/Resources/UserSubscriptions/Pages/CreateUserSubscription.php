<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Pages;

use App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserSubscription extends CreateRecord
{
    protected static string $resource = UserSubscriptionResource::class;
}
