<?php

namespace App\Filament\Admin\Resources\Bookings\Tables;

use App\Services\BookingService;
use App\Services\BookingWhatsApp;
use Carbon\Carbon;
use Filament\Actions\Action as ActionsAction;
use Filament\Actions\ActionGroup as ActionsActionGroup;
use Filament\Actions\BulkAction as ActionsBulkAction;
use Filament\Actions\BulkActionGroup as ActionsBulkActionGroup;
use Filament\Actions\DeleteAction as ActionsDeleteAction;
use Filament\Actions\DeleteBulkAction as ActionsDeleteBulkAction;
use Filament\Actions\EditAction as ActionsEditAction;
use Filament\Actions\ViewAction as ActionsViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
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
            ->heading('Appointment list')
            ->description('Find a booking, review its details, and manage the next step.')
            ->searchPlaceholder('Search clients, salons or services')
            ->striped()
            ->defaultPaginationPageOption(25)
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->emptyStateHeading('No bookings to show')
            ->emptyStateDescription('New appointments will appear here. Try another tab or adjust your filters.')
            ->columns([
                TextColumn::make('salon.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Client')
                    ->weight('semibold')
                    ->description(fn ($record) => $record->email)
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('service.title')
                    ->label('Service')
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Appointment')
                    ->date('D, d M Y')
                    ->description(fn ($record) => Carbon::parse($record->time)->format('g:i A'))
                    ->sortable(),
                TextColumn::make('time')
                    ->time()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                BadgeColumn::make('status')
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'info' => 'completed',
                        'danger' => 'rejected',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-check-badge' => 'completed',
                        'heroicon-o-x-circle' => 'rejected',
                    ]),

                TextColumn::make('phone')->label('Phone')->placeholder('No phone')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('salon_id')
                    ->label('Salon')
                    ->relationship('salon', 'name', fn (Builder $query) => $query->when(! auth()->user()->hasRole('admin'), fn ($q) => $q->where('created_by', Auth::id())))
                    ->searchable()
                    ->preload()
                    ->columnSpan(['default' => 1, 'xl' => 2]),

                Filter::make('date_range')
                    ->form([
                        Select::make('period')
                            ->label('Appointment date')
                            ->options(['today' => 'Today', 'upcoming' => 'Upcoming', 'custom' => 'Custom range'])
                            ->placeholder('All dates')
                            ->columnSpan(fn (Get $get): array => ['default' => 1, 'md' => $get('period') === 'custom' ? 1 : 3])
                            ->live(),
                        DatePicker::make('date_from')
                            ->label('From date')
                            ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                        DatePicker::make('date_until')
                            ->label('To date')
                            ->visible(fn (Get $get): bool => $get('period') === 'custom'),
                    ])
                    ->columns(['default' => 1, 'md' => 3])
                    ->query(function (Builder $query, array $data): Builder {
                        if (($data['period'] ?? null) === 'today') {
                            return $query->whereDate('date', today());
                        }

                        if (($data['period'] ?? null) === 'upcoming') {
                            return $query->whereDate('date', '>=', today());
                        }

                        if (($data['period'] ?? null) !== 'custom') {
                            return $query;
                        }

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
                        if (($data['period'] ?? null) !== 'custom') {
                            return match ($data['period'] ?? null) {
                                'today' => ['Today’s appointments'],
                                'upcoming' => ['Upcoming appointments'],
                                default => [],
                            };
                        }

                        $indicators = [];

                        if ($data['date_from'] ?? null) {
                            $indicators[] = 'From '.Carbon::parse($data['date_from'])->toFormattedDateString();
                        }

                        if ($data['date_until'] ?? null) {
                            $indicators[] = 'Until '.Carbon::parse($data['date_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    })
                    ->columnSpan(['default' => 1, 'md' => 3, 'xl' => 4]),
            ])
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns(['default' => 1, 'md' => 4, 'xl' => 12])
            ->filtersFormSchema(fn (array $filters, $livewire): array => [
                $livewire->getTabsContentComponent()->columnSpan(['default' => 1, 'md' => 4, 'xl' => 6]),
                ...array_values($filters),
            ])
            ->recordActions([
                ActionsAction::make('whatsapp')
                    ->label('Open WhatsApp')->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn ($record) => route('bookings.whatsapp', $record))
                    ->visible(fn ($record) => app(BookingWhatsApp::class)->phone($record->phone) !== null)
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
                            app(BookingService::class)->changeStatus($record, 'approved', auth()->user());

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
                            app(BookingService::class)->changeStatus($record, 'rejected', auth()->user());

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
                            $records->each(fn ($record) => app(BookingService::class)->changeStatus($record, 'approved', auth()->user()));

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
                            $records->each(fn ($record) => app(BookingService::class)->changeStatus($record, 'rejected', auth()->user()));

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
