<?php

namespace Database\Seeders;

use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunAjarans = [
            [
                'kode' => '2023/2024',
                'nama' => 'Tahun Ajaran 2023/2024',
                'tanggal_mulai' => '2023-07-15',
                'tanggal_selesai' => '2024-06-30',
                'is_active' => false,
            ],
            [
                'kode' => '2024/2025',
                'nama' => 'Tahun Ajaran 2024/2025',
                'tanggal_mulai' => '2024-07-15',
                'tanggal_selesai' => '2025-06-30',
                'is_active' => false,
            ],
            [
                'kode' => '2025/2026',
                'nama' => 'Tahun Ajaran 2025/2026',
                'tanggal_mulai' => '2025-07-15',
                'tanggal_selesai' => '2026-06-30',
                'is_active' => true,
            ],
        ];

        foreach ($tahunAjarans as $data) {
            TahunAjaran::firstOrCreate(
                ['kode' => $data['kode']],
                $data
            );
        }
    }
}
