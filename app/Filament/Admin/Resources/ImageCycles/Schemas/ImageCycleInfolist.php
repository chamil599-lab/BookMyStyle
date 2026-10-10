<?php

namespace App\Filament\Admin\Resources\ImageCycles\Schemas;

use App\Models\ImageCycle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ImageCycleInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('alt')
                    ->placeholder('-'),
                TextEntry::make('src_url')
                    ->placeholder('-'),
                TextEntry::make('upload_path')
                    ->placeholder('-'),
                TextEntry::make('order')
                    ->numeric(),
                TextEntry::make('status')
                    ->numeric(),
                TextEntry::make('created_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('updated_by')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn (ImageCycle $record): bool => $record->trashed()),
            ]);
    }
}
