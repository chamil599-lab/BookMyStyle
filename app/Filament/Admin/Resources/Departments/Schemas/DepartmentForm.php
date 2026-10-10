<?php

namespace App\Filament\Admin\Resources\Departments\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->afterStateUpdated(function (string $operation, $state, $set) {
                    $set('slug', \Illuminate\Support\Str::slug($state));
                }),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('meta_title'),
                TextInput::make('meta_description'),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
