<?php

namespace App\Filament\Admin\Resources\Services\Schemas;

use App\Enums\Enums\ServiceEnum;
use App\Models\Salon;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('salon_id')
                    ->label('Salon')
                    ->options(fn () => Salon::query()
                        ->when(! \App\Support\ManagementAccess::admin(auth()->user()), fn ($query) => $query->where('created_by', auth()->id()))
                        ->pluck('name', 'id'))
                    ->required(fn ($operation) => $operation === 'create')
                    ->visible(fn ($operation) => $operation === 'create')
                    ->dehydrated(fn ($operation) => $operation === 'create')
                    ->searchable()
                    ->preload()
                    ->helperText('Select the salon to add this service to'),

                TextInput::make('title')
                    ->live(onBlur: true)
                    ->required()
                    ->afterStateUpdated(
                        fn ($state, $set) => $set('slug', \Illuminate\Support\Str::slug($state))
                    ),
                TextInput::make('slug')
                    ->required(),
                RichEditor::make('description')
                    ->required()
                    ->columnSpanFull(),
                Select::make('department_id')
                    ->relationship('department', 'name')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->Reactive(),
                Select::make('category_id')
                    ->relationship('category', 'name', modifyQueryUsing: function (Builder $query, callable $get) {
                        $departmentId = $get('department_id');
                        if ($departmentId) {
                            return $query->where('department_id', $departmentId);
                        }

                        return $query;
                    })
                    ->preload()
                    ->searchable()
                    ->required(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix('LKR'),
                TextInput::make('duration')
                    ->label('Duration (minutes)')
                    ->numeric()
                    ->required()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(1440)
                    ->suffix('min')
                    ->placeholder('e.g., 30'),
                Select::make('status')
                    ->options(ServiceEnum::labels())
                    ->default(ServiceEnum::Draft->value)
                    ->required(),
                /*    Select::make('created_by')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('updated_by')
                    ->relationship('user', 'name')
                    ->required(),
*/
            ]);
    }
}
