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
                        'danger' => 'rejected',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                    ]),

                TextColumn::make('phone')
                    ->label('Send WhatsApp')
                    ->html()
                    ->formatStateUsing(function ($record) {
                        if (! $record->phone) {
                            return '<span class="text-gray-400">No Phone</span>';
                        }

                        $phone = preg_replace('/[^0-9]/', '', $record->phone);

                        $message = match ($record->status) {
                            'approved' => view('whatsapp.booking-approved', ['booking' => $record])->render(),
                            'rejected' => view('whatsapp.booking-rejected', ['booking' => $record])->render(),
                            default => view('whatsapp.booking-pending', ['booking' => $record])->render(),
                        };

                        $message = trim(preg_replace('/\n\s+/', "\n", $message));
                        $url = "https://wa.me/{$phone}?text=".urlencode($message);

                        return '<a href="'.$url.'" target="_blank"
                                    class="inline-block px-3 py-1 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    WhatsApp
                                </a>';
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                // Column 1: Status + Salon (stacked)
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                    ])
                    ->placeholder('All Statuses')
                    ->columnSpan(1),

                SelectFilter::make('salon_id')
                    ->label('Salon')
                    ->relationship('salon', 'name', fn (Builder $query) => $query->where('created_by', Auth::id()))
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
