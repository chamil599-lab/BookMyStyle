<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Concerns\HandlesServiceValidation;
use App\Models\User;
use App\Services\SubscriptionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;

class CreateUser extends CreateRecord
{
    use HandlesServiceValidation;

    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return $this->withFormValidation(fn () => DB::transaction(function () use ($data) {
            Gate::forUser(auth()->user()->fresh())->authorize('create', User::class);
            $role = Validator::make(['role' => $this->data['role'] ?? null],
                ['role' => 'required|in:admin,owner,user,customer'])->validate()['role'];
            $data = array_intersect_key($data, array_flip(['name', 'email', 'password', 'is_approved']));
            if ($data['is_approved'] ?? false) {
                $data['approved_at'] = now();
                $data['approved_by'] = auth()->id();
            }
            $record = parent::handleRecordCreation($data);
            $record->assignRole($role);
            if ($role === 'owner') {
                app(SubscriptionService::class)->startRegistrationTrial($record);
            }

            return $record;
        }, 3));
    }
}
