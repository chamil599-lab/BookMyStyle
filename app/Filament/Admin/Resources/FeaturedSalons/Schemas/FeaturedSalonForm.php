<?php

namespace App\Filament\Admin\Resources\FeaturedSalons\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use App\Models\Salon;

class FeaturedSalonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('salon_id')
                    ->label('Salon')
                    ->options(Salon::pluck('name', 'id')->toArray())
                    ->searchable()
                    ->required()
                    ->columnSpanFull(),

                DateTimePicker::make('starts_at')
                    ->label('Feature Start Date & Time')
                    ->nullable()
                    ->helperText('Leave empty to feature immediately'),

                DateTimePicker::make('ends_at')
                    ->label('Feature End Date & Time')
                    ->nullable()
                    ->helperText('Leave empty to feature indefinitely'),

                Toggle::make('active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Automatically deactivated when end date passes'),

                Hidden::make('created_by'),
                Hidden::make('updated_by'),
            ]);
    }
}
