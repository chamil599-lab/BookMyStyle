<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\RolesEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->required(fn ($operation) => $operation === 'create')
                            ->dehydrated(fn ($state) => filled($state))
                            ->label(fn ($operation) => $operation === 'create' ? 'Password' : 'New Password (leave blank to keep current)'),
                    ]),
                Section::make('Role & Approval')
                    ->schema([
                        Select::make('role')
                            ->label('Role')
                            ->options([
                                RolesEnum::ADMIN->value => 'Admin',
                                RolesEnum::OWNER->value => 'Owner',
                                RolesEnum::USER->value => 'User',
                                RolesEnum::CUSTOMER->value => 'Customer',
                            ])
                            ->required()
                            ->default(RolesEnum::USER->value)
                            ->disabled(fn ($record) => $record?->hasRole(RolesEnum::ADMIN->value))
                            ->helperText(fn ($record) => $record?->hasRole(RolesEnum::ADMIN->value) ? 'Admin role cannot be changed' : null)
                            ->dehydrated(false)
                            ->afterStateHydrated(function ($component, $record) {
                                if ($record) {
                                    $component->state($record->roles->first()?->name);
                                }
                            }),
                        Toggle::make('is_approved')
                            ->label('Approved')
                            ->disabled(fn ($record) => $record?->hasRole(RolesEnum::ADMIN->value))
                            ->helperText('Toggle to approve or revoke user access'),
                        Placeholder::make('approved_at')
                            ->label('Approved At')
                            ->content(fn ($record) => $record?->approved_at?->format('M d, Y H:i') ?? 'Not approved')
                            ->visible(fn ($operation) => $operation === 'edit'),
                        Placeholder::make('approved_by_name')
                            ->label('Approved By')
                            ->content(fn ($record) => $record?->approvedBy?->name ?? 'N/A')
                            ->visible(fn ($operation) => $operation === 'edit'),
                    ]),
            ]);
    }
}