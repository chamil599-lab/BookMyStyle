<?php

namespace App\Filament\Admin\Resources\ImageCycles\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ImageCycleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title'),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('alt'),
                TextInput::make('src_url')
                    ->url()
                    ->rules(['url:http,https'])
                    ->maxLength(2048)
                    ->helperText('Optional: provide an external image URL. If set, it will be used instead of an uploaded file.'),
                FileUpload::make('upload_path')
                    ->directory('image_cycles')
                    ->disk('public')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                    ->rules(['dimensions:max_width=6000,max_height=6000'])
                    ->imagePreviewHeight('150')
                    ->maxSize(10240)
                    ->helperText('Optional: upload an image file as a fallback when no external URL is provided.'),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('created_by')
                    ->numeric(),
                TextInput::make('updated_by')
                    ->numeric(),
            ]);
    }
}
