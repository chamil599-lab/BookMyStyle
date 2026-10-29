<?php

namespace App\Filament\Admin\Resources\Salons\Pages;

use App\Filament\Admin\Resources\Salons\SalonResource;
use App\Filament\Concerns\HandlesServiceValidation;
use App\Services\CatalogueManagement;
use App\Services\SalonClosureService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class EditSalon extends EditRecord
{
    use HandlesServiceValidation;

    protected static string $resource = SalonResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $updated = $this->withFormValidation(fn () => app(CatalogueManagement::class)->updateSalon(auth()->user(), $record, $data));
        $record->setRawAttributes($updated->getAttributes(), true);

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('closedDates')
                ->label('Closed dates')
                ->icon('heroicon-o-calendar-days')
                ->modalHeading('Manage salon closed dates')
                ->fillForm(fn () => ['dates' => $this->record->closedDates()->where('date', '>=', today()->format('Y-m-d'))->orderBy('date')->pluck('date')->all()])
                ->schema([
                    ViewField::make('dates')->view('filament.forms.closed-dates-calendar'),
                    TextInput::make('reason')->maxLength(255)->helperText('Optional reason for newly closed dates.'),
                ])
                ->action(function (array $data): void {
                    try {
                        app(SalonClosureService::class)->save(auth()->user(), $this->record, $data);
                    } catch (ValidationException $exception) {
                        throw ValidationException::withMessages(collect($exception->errors())->mapWithKeys(fn ($messages, $field) => ['mountedActions.0.data.'.$field => $messages])->all());
                    }
                    Notification::make()->title('Closed dates saved')->success()->send();
                }),
            ViewAction::make(), DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
