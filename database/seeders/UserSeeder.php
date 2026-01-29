<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            // 1. Super Admin (Kepala Sekolah)
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@sisekolah.test',
                'role' => 'super_admin',
            ],
            // 2. Bendahara
            [
                'name' => 'Bendahara',
                'email' => 'bendahara@sisekolah.test',
                'role' => 'bendahara',
            ],
            // 3. Tata Usaha
            [
                'name' => 'Tata Usaha',
                'email' => 'tu@sisekolah.test',
                'role' => 'tata_usaha',
            ],
            // 4. Guru BK
            [
                'name' => 'Guru BK',
                'email' => 'gurubk@sisekolah.test',
                'role' => 'guru_bk',
            ],
            // 5. Guru
            [
                'name' => 'Guru',
                'email' => 'guru@sisekolah.test',
                'role' => 'guru',
            ],
            // 6. Wali Kelas
            [
                'name' => 'Wali Kelas',
                'email' => 'walikelas@sisekolah.test',
                'role' => 'wali_kelas',
            ],
            // 7. Petugas Piket
            [
                'name' => 'Petugas Piket',
                'email' => 'piket@sisekolah.test',
                'role' => 'petugas_piket',
            ],
            // Legacy admin role (for backward compatibility)
            [
                'name' => 'Admin User',
                'email' => 'admin@sisekolah.test',
                'role' => 'admin',
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }
    }
}
