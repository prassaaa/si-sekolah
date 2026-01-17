<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\UnitPos;
use Illuminate\Database\Seeder;

class UnitPosSeeder extends Seeder
{
    public function run(): void
    {
        $akunKas = Akun::where('kode', '1-1001')->first();

        $units = [
            [
                'kode' => 'POS-01',
                'nama' => 'Loket Utama',
                'alamat' => 'Gedung Utama Lt. 1',
                'telepon' => '021-1234567',
                'akun_id' => $akunKas?->id,
                'is_active' => true,
            ],
            [
                'kode' => 'POS-02',
                'nama' => 'Loket Koperasi',
                'alamat' => 'Gedung Koperasi',
                'telepon' => '021-1234568',
                'akun_id' => $akunKas?->id,
                'is_active' => true,
            ],
            [
                'kode' => 'POS-03',
                'nama' => 'Loket Online',
                'alamat' => 'Virtual',
                'telepon' => '-',
                'akun_id' => $akunKas?->id,
                'is_active' => true,
            ],
        ];

        foreach ($units as $unit) {
            UnitPos::firstOrCreate(['kode' => $unit['kode']], $unit);
        }

        $this->command->info('Unit POS seeded successfully: '.count($units).' records');
    }
}
