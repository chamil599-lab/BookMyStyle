<?php

namespace App\Filament\Admin\Pages\Auth;

use App\Mail\OwnerRegistered;
use App\RolesEnum;
use Filament\Auth\Pages\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class Register extends BaseRegister
{
    protected function handleRegistration(array $data): Model
    {
        $user = $this->getUserModel()::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_approved' => false,
        ]);

        $user->assignRole(RolesEnum::OWNER->value);

        // ✅ Send registration confirmation email
        Mail::to($user->email)->send(new OwnerRegistered($user));

        return $user;
    }
}
