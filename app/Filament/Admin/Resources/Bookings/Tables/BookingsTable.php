<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use Filament\Actions\Action as ActionsAction;
use Filament\Actions\ActionGroup as ActionsActionGroup;
use Filament\Actions\BulkAction as ActionsBulkAction;
use Filament\Actions\BulkActionGroup as ActionsBulkActionGroup;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Actions\DeleteBulkAction as ActionsDeleteBulkAction;
use Filament\Actions\EditAction as ActionsEditAction;
use Filament\Actions\ViewAction as ActionsViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('salon.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('service.title')
                    ->label('Service')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('time')
                    ->time()
                    ->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'pending',
                        'success' => 'approved',
                        'info' => 'completed',
                        'danger' => 'rejected',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                    ]),

                TextColumn::make('phone')->label('Phone')->placeholder('No phone'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Column 1: Status + Salon (stacked)
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                    ])
                    ->placeholder('All Statuses')
                    ->columnSpan(1),

                SelectFilter::make('salon_id')
                    ->label('Salon')
                    ->relationship('salon', 'name', fn (Builder $query) => $query->when(! auth()->user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id())))
                    ->searchable()
                    ->preload()
                    ->columnSpan(1),

                // Column 2: Date Range
                Filter::make('date_range')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('date_from')
                            ->label('From Date'),
                        \Filament\Forms\Components\DatePicker::make('date_until')
                            ->label('To Date'),
                    ])
                    ->columns(1)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['date_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'From '.\Carbon\Carbon::parse($data['date_from'])->toFormattedDateString();
                        }

                        if ($data['date_until'] ?? null) {
                            $indicators[] = 'Until '.\Carbon\Carbon::parse($data['date_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    })
                    ->columnSpan(1),

                // Column 3: Toggle Buttons
                Filter::make('today')
                    ->label("Today's Bookings")
                    ->query(fn (Builder $query): Builder => $query->whereDate('date', today()))
                    ->toggle()
                    ->columnSpan(1),

                Filter::make('upcoming')
                    ->label('Upcoming Bookings')
                    ->query(fn (Builder $query): Builder => $query->whereDate('date', '>=', today()))
                    ->toggle()
                    ->columnSpan(1),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(3) // ✅ 3 columns layout
            ->filtersLayout(FiltersLayout::AboveContent) // ✅ Filters above content
            ->recordActions([
                ActionsAction::make('whatsapp')
                    ->label('Open WhatsApp')->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn ($record) => app(\App\Services\BookingWhatsApp::class)->url(auth()->user(), $record))
                    ->visible(fn ($record) => app(\App\Services\BookingWhatsApp::class)->phone($record->phone) !== null)
                    ->openUrlInNewTab()->extraAttributes(['rel' => 'noopener noreferrer']),
                ActionsActionGroup::make([
                    ActionsViewAction::make(),
                    ActionsEditAction::make(),

                    ActionsAction::make('approve')
                        ->label('Approve')
                        ->color('success')
                        ->icon('heroicon-o-check')
                        ->visible(fn ($record) => $record->status !== 'approved')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            Gate::authorize('updateStatus', $record);
                            app(\App\Services\BookingService::class)->changeStatus($record, 'approved', auth()->user());

                            Notification::make()
                                ->title('Booking Approved')
                                ->body("Booking for {$record->name} has been approved.")
                                ->success()
                                ->send();
                        }),

                    ActionsAction::make('reject')
                        ->label('Reject')
                        ->color('danger')
                        ->icon('heroicon-o-x-mark')
                        ->visible(fn ($record) => $record->status !== 'rejected')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            Gate::authorize('updateStatus', $record);
                            app(\App\Services\BookingService::class)->changeStatus($record, 'rejected', auth()->user());

                            Notification::make()
                                ->title('Booking Rejected')
                                ->body("Booking for {$record->name} has been rejected.")
                                ->danger()
                                ->send();
                        }),

                    ActionsDeleteAction::make()
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            Gate::authorize('delete', $record);
                            $record->delete();

                            Notification::make()
                                ->title('Booking Deleted')
                                ->body("Booking for {$record->name} has been deleted.")
                                ->success()
                                ->send();
                        }),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->button()
                    ->color('gray')
                    ->tooltip('Actions')
                    ->label('Actions'),
            ])
            ->bulkActions([
                ActionsBulkActionGroup::make([
                    ActionsBulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => Gate::authorize('updateStatus', $record));
                            $records->each(fn ($record) => app(\App\Services\BookingService::class)->changeStatus($record, 'approved', auth()->user()));

                            $count = $records->count();
                            Notification::make()
                                ->title("{$count} bookings approved")
                                ->success()
                                ->send();
                        }),

                    ActionsBulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => Gate::authorize('updateStatus', $record));
                            $records->each(fn ($record) => app(\App\Services\BookingService::class)->changeStatus($record, 'rejected', auth()->user()));

                            $count = $records->count();
                            Notification::make()
                                ->title("{$count} bookings rejected")
                                ->danger()
                                ->send();
                        }),

                    ActionsDeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete')
                        ->requiresConfirmation(),
                ]),
            ]);
    }
}
