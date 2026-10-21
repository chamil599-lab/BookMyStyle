<?php

namespace App\Services;

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\PermissionsEnum;
use App\RolesEnum;
use Database\Seeders\SubscriptionPackageSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class FreshInstallationService
{
    public function initialize(array $settings): User
    {
        $settings = Validator::make($settings, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:16|max:128',
        ])->validate();

        return DB::transaction(function () use ($settings): User {
            if (User::exists()) {
                throw new RuntimeException('Fresh installation requires an empty users table.');
            }
            foreach (RolesEnum::cases() as $role) {
                Role::findOrCreate($role->value, 'web');
            }
            foreach (PermissionsEnum::cases() as $permission) {
                Permission::findOrCreate($permission->value, 'web');
            }
            Role::findByName('admin', 'web')->syncPermissions(array_column(PermissionsEnum::cases(), 'value'));
            foreach (['owner', 'customer', 'user'] as $role) {
                Role::findByName($role, 'web')->syncPermissions($role === 'owner' ? ['SellServices', 'BuyServices'] : ['BuyServices']);
            }
            if (! SubscriptionPackage::exists()) {
                (new SubscriptionPackageSeeder)->run();
            }
            $user = new User;
            $user->forceFill([
                'name' => $settings['name'], 'email' => $settings['email'],
                'password' => Hash::make($settings['password']), 'email_verified_at' => now(),
                'is_approved' => true, 'approved_at' => now(),
            ])->save();
            $user->assignRole('admin');

            return $user;
        });
    }
}
