<?php

namespace App\Filament\Admin\Resources\SubscriptionPackages\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SubscriptionPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Package Name')
                    ->required()
                    ->helperText('e.g., Salon Starter, Salon Pro'),

                TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique('subscription_packages', 'slug', ignoreRecord: true)
                    ->helperText('URL-friendly identifier'),

                DatePicker::make('start_date')
                    ->label('Start Date')
                    ->nullable()
                    ->helperText('When this package becomes available'),

                DatePicker::make('end_date')
                    ->label('End Date')
                    ->nullable()
                    ->helperText('When this package becomes unavailable'),

                TextInput::make('price')
                    ->label('Price')
                    ->required()
                    ->numeric()
                    ->step('0.01')
                    ->prefix('$'),

                TextInput::make('duration_days')
                    ->label('Duration (Days)')
                    ->required()
                    ->numeric()
                    ->helperText('How many days the subscription lasts'),

                TextInput::make('max_salons')
                    ->label('Max Salons')
                    ->required()
                    ->numeric()
                    ->helperText('Maximum number of salons per owner'),

                TextInput::make('max_staff')
                    ->label('Max Staff Members')
                    ->required()
                    ->numeric()
                    ->helperText('Maximum staff per salon'),

                TextInput::make('max_services')
                    ->label('Max Services')
                    ->required()
                    ->numeric()
                    ->helperText('Maximum services per salon'),

                TextInput::make('max_bookings_per_month')
                    ->label('Max Bookings Per Month')
                    ->required()
                    ->numeric()
                    ->default(100)
                    ->helperText('Maximum bookings allowed per month across all salons'),

                TextInput::make('order_column')
                    ->label('Display Order')
                    ->required()
                    ->numeric()
                    ->default(0)
                    ->helperText('Lower numbers appear first'),

                Toggle::make('analytics')
                    ->label('Analytics Available')
                    ->helperText('Enable analytics dashboard'),

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

                Textarea::make('features')
                    ->label('Features (JSON)')
                    ->helperText('Enter features as JSON array or comma-separated values')
                    ->rows(4),
            ]);
    }
}
