<?php

namespace App\Filament\Admin\Resources\TeamMembers\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TeamMemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('role'),
                Textarea::make('bio')
                    ->columnSpanFull(),
                TextInput::make('src_url')
                    ->url()
                    ->rules(['url:http,https'])
                    ->maxLength(2048),
                FileUpload::make('upload_path')
                    ->directory('team_members')
                    ->disk('public')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp'])
                    ->rules(['dimensions:max_width=6000,max_height=6000'])
                    ->maxSize(10240)
                    ->visibility('public')
                    ->enableOpen()
                    ->enableDownload(),
                TextInput::make('order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('status')
                    ->required(),
            ]);
    }
}
