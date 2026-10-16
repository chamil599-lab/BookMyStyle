<?php

namespace App\Filament\Admin\Resources\Salons\Tables;

use App\Support\PublicUrl;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SalonsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('owner_name')
                    ->searchable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('open_time')
                    ->time(),
                TextColumn::make('close_time')
                    ->time(),
                ImageColumn::make('cover_image')
                    ->disk('public'),
                TextColumn::make('number_of_staff')
                    ->label('Staff')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('facebook_page')
                    ->label('Facebook')
                    ->url(fn ($record) => PublicUrl::link($record->facebook_page))
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),
                TextColumn::make('instagram_page')
                    ->label('Instagram')
                    ->url(fn ($record) => PublicUrl::link($record->instagram_page))
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),
                TextColumn::make('tiktok_page')
                    ->label('TikTok')
                    ->url(fn ($record) => PublicUrl::link($record->tiktok_page))
                    ->openUrlInNewTab()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->limit(30),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
