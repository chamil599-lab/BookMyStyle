<?php

namespace App\Filament\Admin\Resources\DealsPages\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class DealsPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug'),
                TextInput::make('category'),
                TextInput::make('salon'),
                TextInput::make('original_price')
                    ->numeric(),
                TextInput::make('discounted_price')
                    ->numeric(),
                TextInput::make('discount')
                    ->numeric(),
                FileUpload::make('image')
                    ->image(),
                TextInput::make('src_url')
                    ->label('External Image URL')
                    ->url()
                    ->helperText('Optional: external image URL. If provided, it will be used instead of an uploaded file.'),
                TextInput::make('badge'),
                TextInput::make('badge_color'),
                Textarea::make('description')
                    ->columnSpanFull(),
                DatePicker::make('valid_until'),
                TextInput::make('features'),
                
                TextInput::make('status')
                    ->required()
                    ->numeric()
                    ->default(1),
            ]);
    }
}
