<?php

namespace Database\Seeders;

use App\Models\KategoriPembayaran;
use Illuminate\Database\Seeder;

class KategoriPembayaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoriPembayarans = [
            [
                'kode' => 'SPP',
                'nama' => 'Sumbangan Pembinaan Pendidikan',
                'deskripsi' => 'SPP Bulanan',
                'urutan' => 1,
                'is_active' => true,
            ],
            [
                'kode' => 'UG',
                'nama' => 'Uang Gedung',
                'deskripsi' => 'Biaya pembangunan dan pemeliharaan gedung',
                'urutan' => 2,
                'is_active' => true,
            ],
            [
                'kode' => 'SR',
                'nama' => 'Seragam',
                'deskripsi' => 'Biaya seragam sekolah',
                'urutan' => 3,
                'is_active' => true,
            ],
            [
                'kode' => 'BK',
                'nama' => 'Buku',
                'deskripsi' => 'Biaya buku pelajaran',
                'urutan' => 4,
                'is_active' => true,
            ],
            [
                'kode' => 'KG',
                'nama' => 'Kegiatan',
                'deskripsi' => 'Biaya kegiatan ekstrakurikuler',
                'urutan' => 5,
                'is_active' => true,
            ],
            [
                'kode' => 'UJ',
                'nama' => 'Ujian',
                'deskripsi' => 'Biaya ujian semester/akhir',
                'urutan' => 6,
                'is_active' => true,
            ],
            [
                'kode' => 'WS',
                'nama' => 'Wisuda',
                'deskripsi' => 'Biaya wisuda dan kelulusan',
                'urutan' => 7,
                'is_active' => true,
            ],
            [
                'kode' => 'TB',
                'nama' => 'Tabungan',
                'deskripsi' => 'Tabungan wajib siswa',
                'urutan' => 8,
                'is_active' => true,
            ],
            [
                'kode' => 'LN',
                'nama' => 'Lainnya',
                'deskripsi' => 'Biaya lain-lain',
                'urutan' => 9,
                'is_active' => true,
            ],
        ];

        foreach ($kategoriPembayarans as $kategori) {
            KategoriPembayaran::firstOrCreate(
                ['kode' => $kategori['kode']],
                $kategori
            );
        }

        $this->command->info('Kategori Pembayaran seeded successfully: '.count($kategoriPembayarans).' records');
    }
}
