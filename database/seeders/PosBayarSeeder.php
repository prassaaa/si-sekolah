<?php

namespace Database\Seeders;

use App\Models\PosBayar;
use Illuminate\Database\Seeder;

class PosBayarSeeder extends Seeder
{
    public function run(): void
    {
        $posBayars = [
            ['kode' => 'SPP', 'nama' => 'SPP Bulanan', 'deskripsi' => 'Sumbangan Pembinaan Pendidikan bulanan', 'is_active' => true],
            ['kode' => 'UG', 'nama' => 'Uang Gedung', 'deskripsi' => 'Biaya pembangunan dan pemeliharaan gedung', 'is_active' => true],
            ['kode' => 'SR', 'nama' => 'Seragam', 'deskripsi' => 'Biaya seragam sekolah', 'is_active' => true],
            ['kode' => 'BK', 'nama' => 'Buku', 'deskripsi' => 'Biaya buku pelajaran', 'is_active' => true],
            ['kode' => 'KG', 'nama' => 'Kegiatan', 'deskripsi' => 'Biaya kegiatan ekstrakurikuler', 'is_active' => true],
            ['kode' => 'UJ', 'nama' => 'Ujian', 'deskripsi' => 'Biaya ujian semester/akhir', 'is_active' => true],
            ['kode' => 'WS', 'nama' => 'Wisuda', 'deskripsi' => 'Biaya wisuda dan kelulusan', 'is_active' => true],
            ['kode' => 'LN', 'nama' => 'Lainnya', 'deskripsi' => 'Biaya lain-lain', 'is_active' => true],
        ];

        foreach ($posBayars as $pos) {
            PosBayar::firstOrCreate(['kode' => $pos['kode']], $pos);
        }

        $this->command->info('Pos Bayar seeded successfully: '.count($posBayars).' records');
    }
}
