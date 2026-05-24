<?php

namespace Database\Seeders;

use App\Models\RfidDevice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RfidDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            [
                'nama' => 'Reader Gerbang Utama (Masuk)',
                'kode' => 'GERBANG-IN-01',
                'jenis' => 'gerbang_masuk',
                'lokasi' => 'Gerbang Utama Depan',
            ],
            [
                'nama' => 'Reader Gerbang Utama (Pulang)',
                'kode' => 'GERBANG-OUT-01',
                'jenis' => 'gerbang_pulang',
                'lokasi' => 'Gerbang Utama Depan',
            ],
            [
                'nama' => 'Reader Pos Satpam',
                'kode' => 'POS-SATPAM-01',
                'jenis' => 'serbaguna',
                'lokasi' => 'Pos Satpam',
            ],
        ];

        foreach ($devices as $data) {
            RfidDevice::firstOrCreate(
                ['kode' => $data['kode']],
                [
                    ...$data,
                    'api_token' => Hash::make(Str::random(60)),
                    'is_active' => true,
                ]
            );
        }
    }
}
