<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Mail\UserApproved;
use App\Mail\UserRejected;
use App\RolesEnum;
use Filament\Actions\Action as ActionsAction;
use Filament\Actions\ActionGroup as ActionsActionGroup;
use Filament\Actions\BulkActionGroup as ActionsBulkActionGroup;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Actions\DeleteBulkAction as ActionsDeleteBulkAction;
use Filament\Actions\EditAction as ActionsEditAction;
use Filament\Actions\ViewAction as ActionsViewAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('roles.name')
                    ->badge()
                    ->label('Role')
                    ->default('No role')
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'owner' => 'success',
                        'user' => 'info',
                        default => 'gray',
                    }),
                IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approved'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('is_approved')
                    ->options([
                        '1' => 'Approved',
                        '0' => 'Pending',
                    ])
                    ->label('Status'),
            ])
            ->recordActions([
                ActionsViewAction::make(),
                ActionsEditAction::make()
                    ->visible(fn ($record) => !$record->hasRole(RolesEnum::ADMIN->value)),
                ActionsActionGroup::make([
                    ActionsAction::make('approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => !$record->is_approved && !$record->hasRole(RolesEnum::ADMIN->value))
                        ->form([
                            Select::make('role')
                                ->label('Assign Role')
                                ->options([
                                    'owner' => 'Owner',
                                    'user' => 'User',
                                ])
                                ->required()
                                ->default('owner'),
                        ])
                        ->action(function ($record, array $data) {
                            $role = $data['role'] ?? 'owner';
                            $record->syncRoles([$role]);
                            $record->approve();
                            
                            // ✅ Add logging BEFORE sending
                            \Log::info('About to send approval email', [
                                'user_id' => $record->id,
                                'email' => $record->email,
                                'name' => $record->name
                            ]);
                            
                            // Send approval email
                            Mail::to($record->email)->send(new UserApproved($record));
                            
                            // ✅ Add logging AFTER sending
                            \Log::info('Approval email sent successfully');
                            
                            Notification::make()
                                ->title('User approved successfully')
                                ->body("Role assigned: {$role}. Approval email sent.")
                                ->success()
                                ->send();
                        })
                        ->modalHeading('Approve User')
                        ->modalDescription('Assign a role and approve this user.'),
                    ActionsAction::make('reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => !$record->is_approved && !$record->hasRole(RolesEnum::ADMIN->value))
                        ->action(function ($record) {
                            // ✅ Send rejection email before deleting
                            Mail::to($record->email)->send(new UserRejected($record));
                            
                            $record->delete();
                            
                            Notification::make()
                                ->title('User rejected and deleted')
                                ->body('Rejection email sent.')
                                ->warning()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Reject User')
                        ->modalDescription('Are you sure? This will permanently delete the user.'),
                    ActionsDeleteAction::make()
                        ->visible(fn ($record) => $record->is_approved && !$record->hasRole(RolesEnum::ADMIN->value)),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->visible(fn ($record) => !$record->hasRole(RolesEnum::ADMIN->value)),
            ])
            ->bulkActions([
                ActionsBulkActionGroup::make([
                    ActionsDeleteBulkAction::make(),
                ]),
            ]);
    }
}