<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Mail\OwnerRegistered;
use App\RolesEnum;
use App\Services\AccountService;
use Filament\Auth\Pages\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): Model
    {
        $user = app(AccountService::class)->register($data + [
            'password_confirmation' => $data['password'], 'role' => RolesEnum::OWNER->value,
        ], dispatchEvent: false);

        // ✅ Send registration confirmation email
        Mail::to($user->email)->send(new OwnerRegistered($user));

        return $user;
    }
}
