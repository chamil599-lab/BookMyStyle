<?php

namespace App\Filament\Admin\Resources\ServicePages\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServicePageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                TextInput::make('slug'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price'),
                TextInput::make('duration'),
                FileUpload::make('image')
                    ->disk('public')
                    ->directory('service_pages')
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
                TextInput::make('features'),
                TextInput::make('category_id'),
                TextInput::make('category_name'),
                TextInput::make('category_color'),

                TextInput::make('status')
                    ->required()
                    ->default('draft'),
            ]);
    }
}
