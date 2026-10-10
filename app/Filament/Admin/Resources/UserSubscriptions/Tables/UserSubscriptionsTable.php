<?php

namespace App\Filament\Admin\Resources\UserSubscriptions\Tables;

use App\RolesEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UserSubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        /** @var \App\Models\User|null $user */
        $user = Filament::auth()->user();
        $isAdmin = $user?->hasRole(RolesEnum::ADMIN->value) ?? false;

        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->visible($isAdmin),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->searchable(),
                TextColumn::make('package.price')
                    ->label('Price')
                    ->money('LKR')
                    ->sortable(),
                TextColumn::make('amount_paid')
                    ->label('Amount Paid')
                    ->numeric()
                    ->sortable()
                    ->visible($isAdmin),
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->searchable(),
                TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'expired' => 'danger',
                        'canceled' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('starts_at')
                    ->label('Starts At')
                    ->dateTime()
                    ->sortable()
                    ->visible($isAdmin),
                TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->dateTime()
                    ->sortable()
                    ->visible($isAdmin),
                TextColumn::make('cancelled_at')
                    ->label('Cancelled At')
                    ->dateTime()
                    ->sortable()
                    ->visible($isAdmin),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'pending' => 'Pending',
                        'expired' => 'Expired',
                        'canceled' => 'Canceled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible($isAdmin),
                Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Subscription')
                    ->modalDescription('Activate this subscription and set dates based on the package duration.')
                    ->form([
                        TextInput::make('amount_paid')
                            ->label('Verified Amount Paid (LKR)')
                            ->numeric()
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $package = $record->package;

                        // If this is a renewal, start after the parent subscription ends
                        if ($record->renewed_from) {
                            $parent = $record->renewedFrom;
                            $startsAt = $parent && $parent->ends_at && $parent->ends_at->isFuture()
                                ? $parent->ends_at
                                : now();
                        } else {
                            $startsAt = now();
                        }

                        $record->update([
                            'status' => 'active',
                            'amount_paid' => $data['amount_paid'],
                            'starts_at' => $startsAt,
                            'ends_at' => $startsAt->copy()->addDays($package->duration_days),
                        ]);
                        Notification::make()
                            ->title('Subscription Approved')
                            ->body("Activated for {$record->user->name} until {$record->ends_at->format('M d, Y')}.")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $isAdmin && $record->status === 'pending'),
                Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Reject Subscription')
                    ->modalDescription('Are you sure you want to reject this subscription request?')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'canceled',
                            'cancelled_at' => now(),
                        ]);
                        Notification::make()
                            ->title('Subscription Rejected')
                            ->body("Rejected subscription for {$record->user->name}.")
                            ->warning()
                            ->send();
                    })
                    ->visible(fn ($record) => $isAdmin && $record->status === 'pending'),
            ])
            ->toolbarActions([]);
    }
}
