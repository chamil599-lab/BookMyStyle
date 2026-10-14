<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

final class AccountDeletion
{
    public function selfDelete(User $actor, string $password): bool
    {
        return DB::transaction(function () use ($actor, $password) {
            $record = User::whereKey($actor->id)->lockForUpdate()->firstOrFail();
            abort_if($record->hasRole(['admin', 'owner']), 403);
            if (! $record->password || ! Hash::check($password, $record->password)) {
                throw ValidationException::withMessages(['password' => __('auth.password')]);
            }

            return $this->remove($record);
        }, 3);
    }

    public function managedDelete(User $actor, User $target): bool
    {
        return DB::transaction(function () use ($actor, $target) {
            $record = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('delete', $record);

            return $this->remove($record);
        }, 3);
    }

    private function remove(User $record): bool
    {
        if ($record->salons()->exists() || $record->subscriptions()->exists()) {
            throw ValidationException::withMessages(['password' => 'This account has salon or subscription records and needs administrative review before deletion.']);
        }
        $record->tokens()->delete();
        $record->notifications()->delete();
        Password::deleteToken($record);
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                ->where('user_id', $record->id)->delete();
        }

        return (bool) $record->delete();
    }
}
