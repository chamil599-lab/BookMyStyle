<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Schemas;

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\RolesEnum;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class UserSubscriptionForm
{
    private static function isAdmin(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user?->hasRole(RolesEnum::ADMIN->value) ?? false;
    }

    public static function configure(Schema $schema): Schema
    {
        $isAdmin = self::isAdmin();

        return $schema
            ->components([
                // Admin only: Owner selector
                Select::make('user_id')
                    ->disabledOn('edit')
                    ->label('Owner')
                    ->options(
                        User::role(RolesEnum::OWNER->value)
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->required()
                    ->visible($isAdmin),

                // Select the package when creating a new request.
                Select::make('package_id')
                    ->disabledOn('edit')
                    ->label('Select Package')
                    ->options(
                        SubscriptionPackage::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray()
                    )
                    ->searchable()
                    ->required()
                    ->live(),

                // Display package amount
                TextInput::make('package_amount')
                    ->label('Package Amount (LKR)')
                    ->readOnly()
                    ->disabled()
                    ->placeholder('Select a package to see amount')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record && $record->package) {
                            return 'LKR '.number_format($record->package->price, 2);
                        }

                        return null;
                    }),

                // Payment method - editable for owners
                Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'bank_transfer' => 'Bank Transfer',
                        'one_pay' => 'One Pay',
                    ])
                    ->required()
                    ->live(),

                // Admin only fields
                TextInput::make('amount_paid')
                    ->disabled()
                    ->dehydrated(false)
                    ->label('Amount Paid')
                    ->numeric()
                    ->visible($isAdmin),

                TextInput::make('transaction_id')
                    ->label('Transaction ID')
                    ->placeholder('Enter transaction ID')
                    ->helperText('ID from your payment gateway')
                    ->visible(fn (Get $get): bool => $get('payment_method') === 'bank_transfer')
                    ->required(fn (Get $get): bool => $get('payment_method') === 'bank_transfer'),

                Select::make('status')
                    ->disabled()
                    ->dehydrated(false)
                    ->label('Status')
                    ->options(['active' => 'Active', 'expired' => 'Expired', 'canceled' => 'Canceled', 'pending' => 'Pending'])
                    ->default('pending')
                    ->required()
                    ->visible($isAdmin),

                DateTimePicker::make('starts_at')
                    ->disabled()
                    ->dehydrated(false)
                    ->label('Starts At')
                    ->visible($isAdmin),

                DateTimePicker::make('ends_at')
                    ->disabled()
                    ->dehydrated(false)
                    ->label('Ends At')
                    ->visible($isAdmin),

                DateTimePicker::make('cancelled_at')
                    ->disabled()
                    ->dehydrated(false)
                    ->label('Cancelled At')
                    ->visible($isAdmin),

                KeyValue::make('features_snapshot')
                    ->formatStateUsing(fn ($state) => array_map(fn ($value) => is_array($value) ? json_encode($value) : $value, $state ?? []))
                    ->disabled()
                    ->dehydrated(false)
                    ->label('Features Snapshot')
                    ->visible($isAdmin),

            ]);
    }
}
