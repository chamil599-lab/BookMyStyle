<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class EditUser extends EditRecord
{
    use \App\Filament\Concerns\HandlesServiceValidation;

    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['password'] = null;
        $data['role'] = $this->record->roles->first()?->name;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->withFormValidation(fn () => DB::transaction(function () use ($record, $data) {
            $record = User::whereKey($record->id)->lockForUpdate()->firstOrFail();
            Gate::forUser(auth()->user()->fresh())->authorize('update', $record);
            $role = Validator::make(['role' => $this->data['role'] ?? null],
                ['role' => 'required|in:admin,owner,user,customer'])->validate()['role'];
            $data = array_intersect_key($data, array_flip(['name', 'email', 'password', 'is_approved']));
            if (array_key_exists('is_approved', $data) && (bool) $data['is_approved'] !== (bool) $record->is_approved) {
                $data['approved_at'] = $data['is_approved'] ? now() : null;
                $data['approved_by'] = $data['is_approved'] ? auth()->id() : null;
            }
            $record->update($data);
            $record->syncRoles([$role]);

            return $record;
        }, 3));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
