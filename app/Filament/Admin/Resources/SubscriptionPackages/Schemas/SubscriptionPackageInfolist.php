<?php

namespace App\Filament\Admin\Resources\SubscriptionPackages\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class SubscriptionPackageInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'), TextEntry::make('slug'), TextEntry::make('price')->money('LKR'),
            TextEntry::make('duration_days'), TextEntry::make('max_salons'), TextEntry::make('max_staff'),
            TextEntry::make('max_services'), TextEntry::make('max_bookings_per_month'),
            IconEntry::make('analytics')->boolean(), IconEntry::make('calendar')->boolean(),
            IconEntry::make('priority_support')->boolean(), IconEntry::make('is_active')->boolean(),
            TextEntry::make('features')->listWithLineBreaks(),
        ]);
    }
}
