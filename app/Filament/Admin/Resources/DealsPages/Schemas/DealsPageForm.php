<?php

namespace App\Filament\Admin\Resources\DealsPages\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
                    ->disk('public')
                    ->directory('deals')
                    ->visibility('public')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                    ->rules(['dimensions:max_width=6000,max_height=6000'])
                    ->maxSize(10240),
                TextInput::make('src_url')
                    ->label('External Image URL')
                    ->url()
                    ->rules(['url:http,https'])
                    ->maxLength(2048)
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
