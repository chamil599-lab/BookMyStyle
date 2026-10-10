<?php

namespace Database\Seeders;

use App\PermissionsEnum;
use App\RolesEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;



class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles
        $userRole = Role::create(['name' => RolesEnum::USER->value]);
        $adminRole = Role::create(['name' => RolesEnum::ADMIN->value]);
        $ownerRole = Role::create(['name' => RolesEnum::OWNER->value]);

        // Create permissions
        $approveOwners = Permission::create(['name' => PermissionsEnum::ApproveOwners->value]);
        $sellServices = Permission::create(['name' => PermissionsEnum::SellServices->value]);
        $buyServices = Permission::create(['name' => PermissionsEnum::BuyServices->value]);

        // Assign permissions to roles (add this section)
        $adminRole->syncPermissions([$approveOwners,$sellServices,$buyServices]);
        $ownerRole->syncPermissions([$sellServices,$buyServices]);
        $userRole->syncPermissions([$buyServices]);

        // You might also want to assign multiple permissions:
        // $adminRole->givePermissionTo([$approveOwners, $sellServices]);
    }
}
