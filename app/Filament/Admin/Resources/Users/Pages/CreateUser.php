<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    use \App\Filament\Concerns\HandlesServiceValidation;

    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return $this->withFormValidation(fn () => \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            \Illuminate\Support\Facades\Gate::forUser(auth()->user()->fresh())->authorize('create', \App\Models\User::class);
            $role = \Illuminate\Support\Facades\Validator::make(['role' => $this->data['role'] ?? null],
                ['role' => 'required|in:admin,owner,user,customer'])->validate()['role'];
            $data = array_intersect_key($data, array_flip(['name', 'email', 'password', 'is_approved']));
            if ($data['is_approved'] ?? false) {
                $data['approved_at'] = now();
                $data['approved_by'] = auth()->id();
            }
            $record = parent::handleRecordCreation($data);
            $record->assignRole($role);

            return $record;
        }, 3));
    }
}
