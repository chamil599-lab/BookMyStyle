<?php

namespace App\Filament\Admin\Resources\SubscriptionPackages\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class SubscriptionPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Package Name')->maxLength(255)
                    ->required()
                    ->helperText('e.g., Salon Starter, Salon Pro'),

                TextInput::make('slug')
                    ->label('Slug')->maxLength(255)->regex('/^[a-z0-9]+(?:-[a-z0-9]+)*$/')
                    ->required()
                    ->unique('subscription_packages', 'slug', ignoreRecord: true)
                    ->helperText('URL-friendly identifier'),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->nullable()
                    ->helperText('When this package becomes available'),

                DatePicker::make('end_date')
                    ->label('End Date')->afterOrEqual(fn (Get $get) => $get('start_date'))
                    ->nullable()
                    ->helperText('When this package becomes unavailable'),

                TextInput::make('price')
                    ->label('Price')
                    ->required()
                    ->numeric()
                    ->step('0.01')
                    ->minValue(0)->maxValue(999999.99)->prefix('LKR'),

                TextInput::make('duration_days')
                    ->label('Duration (Days)')
                    ->required()
                    ->integer()->minValue(1)->maxValue(36500)
                    ->helperText('How many days the subscription lasts'),

                TextInput::make('max_salons')
                    ->label('Max Salons')
                    ->required()
                    ->integer()->minValue(1)->maxValue(1000000)
                    ->helperText('Maximum number of salons per owner'),

                TextInput::make('max_staff')
                    ->label('Max Staff Members')
                    ->required()
                    ->integer()->minValue(1)->maxValue(1000000)
                    ->helperText('Maximum staff per salon'),

                TextInput::make('max_services')
                    ->label('Max Services')
                    ->required()
                    ->integer()->minValue(1)->maxValue(1000000)
                    ->helperText('Maximum services per salon'),

                TextInput::make('max_bookings_per_month')
                    ->label('Max Bookings Per Month')
                    ->required()
                    ->integer()->minValue(1)->maxValue(1000000)
                    ->default(100)
                    ->helperText('Maximum bookings per month across all salons.'),

                TextInput::make('order_column')
                    ->label('Display Order')
                    ->required()
                    ->integer()->minValue(0)->maxValue(1000000)
                    ->default(0)
                    ->helperText('Lower numbers appear first'),

                Toggle::make('analytics')
                    ->label('Analytics Available')
                    ->helperText('Enable analytics dashboard'),

                Toggle::make('calendar')->label('Booking Calendar'),

                Toggle::make('priority_support')
                    ->label('Priority Support')
                    ->helperText('Priority customer support'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Is this package available for purchase?'),

                Toggle::make('is_popular')
                    ->label('Popular Package')
                    ->helperText('Highlight as popular on pricing page'),

                TagsInput::make('features')
                    ->label('Features')->nestedRecursiveRules(['string', 'max:255'])->rules(['array', 'max:50'])
                    ->helperText('Press Enter after each feature.'),
            ]);
    }
}
