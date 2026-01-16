<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $user = Role::firstOrCreate(['name' => 'user']);

        // Create all permissions for User resource
        $permissions = [
            'ViewAny:Users',
            'View:Users',
            'Create:Users',
            'Update:Users',
            'Delete:Users',
            'DeleteAny:Users',
            'ForceDelete:Users',
            'ForceDeleteAny:Users',
            'Restore:Users',
            'RestoreAny:Users',
            'Replicate:Users',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Give all permissions to super_admin
        $superAdmin->syncPermissions(Permission::all());

        // Give limited permissions to admin
        $admin->syncPermissions([
            'ViewAny:Users',
            'View:Users',
            'Create:Users',
            'Update:Users',
        ]);
    }
}
