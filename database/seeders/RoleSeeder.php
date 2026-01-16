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

        // Create all permissions for User resource
        $permissions = [
            // User permissions (singular - based on model name)
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'Delete:User',
            'DeleteAny:User',
            'ForceDelete:User',
            'ForceDeleteAny:User',
            'Restore:User',
            'RestoreAny:User',
            'Replicate:User',
            'Reorder:User',
            // Role permissions
            'ViewAny:Role',
            'View:Role',
            'Create:Role',
            'Update:Role',
            'Delete:Role',
            'DeleteAny:Role',
            'ForceDelete:Role',
            'ForceDeleteAny:Role',
            'Restore:Role',
            'RestoreAny:Role',
            'Replicate:Role',
            'Reorder:Role',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Give all permissions to super_admin
        $superAdmin->syncPermissions(Permission::all());

        // Give limited permissions to admin
        $admin->syncPermissions([
            'ViewAny:User',
            'View:User',
            'Create:User',
            'Update:User',
            'ViewAny:Role',
            'View:Role',
        ]);
    }
}
