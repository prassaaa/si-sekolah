<?php

namespace Database\Seeders;

use App\Models\Pajak;
use Illuminate\Database\Seeder;

class PajakSeeder extends Seeder
{
    public function run(): void
    {
        $pajaks = [
            ['nama' => 'PPN 11%', 'persentase' => 11.00, 'keterangan' => 'Pajak Pertambahan Nilai', 'is_active' => true],
            ['nama' => 'PPh 21', 'persentase' => 5.00, 'keterangan' => 'Pajak Penghasilan Pasal 21', 'is_active' => true],
            ['nama' => 'PPh 23', 'persentase' => 2.00, 'keterangan' => 'Pajak Penghasilan Pasal 23', 'is_active' => true],
            ['nama' => 'PPh Final 0.5%', 'persentase' => 0.50, 'keterangan' => 'PPh Final UMKM', 'is_active' => true],
        ];

        foreach ($pajaks as $pajak) {
            Pajak::firstOrCreate(['nama' => $pajak['nama']], $pajak);
        }

        $this->command->info('Pajak seeded successfully: '.count($pajaks).' records');
    }
}
