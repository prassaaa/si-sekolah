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
            // Activity Log permissions
            'ViewAny:Activity',
            'View:Activity',
            // Sekolah permissions
            'ViewAny:Sekolah',
            'View:Sekolah',
            'Create:Sekolah',
            'Update:Sekolah',
            'Delete:Sekolah',
            'DeleteAny:Sekolah',
            'ForceDelete:Sekolah',
            'ForceDeleteAny:Sekolah',
            'Restore:Sekolah',
            'RestoreAny:Sekolah',
            'Replicate:Sekolah',
            'Reorder:Sekolah',
            // Informasi permissions
            'ViewAny:Informasi',
            'View:Informasi',
            'Create:Informasi',
            'Update:Informasi',
            'Delete:Informasi',
            'DeleteAny:Informasi',
            'ForceDelete:Informasi',
            'ForceDeleteAny:Informasi',
            'Restore:Informasi',
            'RestoreAny:Informasi',
            'Replicate:Informasi',
            'Reorder:Informasi',
            // JabatanPegawai permissions
            'ViewAny:JabatanPegawai',
            'View:JabatanPegawai',
            'Create:JabatanPegawai',
            'Update:JabatanPegawai',
            'Delete:JabatanPegawai',
            'DeleteAny:JabatanPegawai',
            'ForceDelete:JabatanPegawai',
            'ForceDeleteAny:JabatanPegawai',
            'Restore:JabatanPegawai',
            'RestoreAny:JabatanPegawai',
            'Replicate:JabatanPegawai',
            'Reorder:JabatanPegawai',
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
            'ViewAny:Activity',
            'View:Activity',
            'ViewAny:Sekolah',
            'View:Sekolah',
            'ViewAny:Informasi',
            'View:Informasi',
            'Create:Informasi',
            'Update:Informasi',
            'ViewAny:JabatanPegawai',
            'View:JabatanPegawai',
        ]);
    }
}
