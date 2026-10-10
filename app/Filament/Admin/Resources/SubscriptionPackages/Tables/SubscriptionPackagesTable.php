<?php

namespace App\Filament\Admin\Resources\SubscriptionPackages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SubscriptionPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Package Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('End Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('LKR')
                    ->sortable(),

                TextColumn::make('duration_days')
                    ->label('Duration (Days)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('max_salons')
                    ->label('Max Salons')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('max_staff')
                    ->label('Max Staff')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('max_services')
                    ->label('Max Services')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('max_bookings_per_month')
                    ->label('Max Bookings/Month')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('analytics')
                    ->label('Analytics')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('priority_support')
                    ->label('Priority Support')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('order_column')
                    ->label('Order')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    RestoreBulkAction::make(),
                ])
                    ->visible(fn () => true), // Show only restore action for soft-deleted items
            ]);
    }
}
