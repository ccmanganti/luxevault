<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['inventory.view', 'inventory.manage', 'warehouses.manage', 'staff.manage', 'accounts.manage'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        Role::findOrCreate('superadmin', 'web')->syncPermissions(Permission::all());
        Role::findOrCreate('warehouse_owner', 'web')->syncPermissions(['inventory.view', 'inventory.manage', 'warehouses.manage', 'staff.manage']);
        Role::findOrCreate('staff', 'web')->syncPermissions(['inventory.view', 'inventory.manage']);
    }
}
