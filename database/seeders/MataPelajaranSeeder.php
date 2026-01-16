<?php

namespace Database\Seeders;

use App\Models\MataPelajaran;
use Illuminate\Database\Seeder;

class MataPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mataPelajarans = [
            // Kelompok A - Umum
            ['kode' => 'PAI', 'nama' => 'Pendidikan Agama Islam', 'singkatan' => 'PAI', 'kelompok' => 'Kelompok A', 'jam_per_minggu' => 3, 'urutan' => 1],
            ['kode' => 'PKN', 'nama' => 'Pendidikan Kewarganegaraan', 'singkatan' => 'PKn', 'kelompok' => 'Kelompok A', 'jam_per_minggu' => 2, 'urutan' => 2],
            ['kode' => 'BIN', 'nama' => 'Bahasa Indonesia', 'singkatan' => 'BIN', 'kelompok' => 'Kelompok A', 'jam_per_minggu' => 4, 'urutan' => 3],
            ['kode' => 'MTK', 'nama' => 'Matematika', 'singkatan' => 'MTK', 'kelompok' => 'Kelompok A', 'jam_per_minggu' => 4, 'urutan' => 4],
            ['kode' => 'IPA', 'nama' => 'Ilmu Pengetahuan Alam', 'singkatan' => 'IPA', 'kelompok' => 'Kelompok A', 'jam_per_minggu' => 4, 'urutan' => 5],
            ['kode' => 'IPS', 'nama' => 'Ilmu Pengetahuan Sosial', 'singkatan' => 'IPS', 'kelompok' => 'Kelompok A', 'jam_per_minggu' => 3, 'urutan' => 6],

            // Kelompok B
            ['kode' => 'BING', 'nama' => 'Bahasa Inggris', 'singkatan' => 'BING', 'kelompok' => 'Kelompok B', 'jam_per_minggu' => 4, 'urutan' => 7],
            ['kode' => 'PJOK', 'nama' => 'Pendidikan Jasmani, Olahraga dan Kesehatan', 'singkatan' => 'PJOK', 'kelompok' => 'Kelompok B', 'jam_per_minggu' => 2, 'urutan' => 8],
            ['kode' => 'SENBUD', 'nama' => 'Seni Budaya', 'singkatan' => 'SB', 'kelompok' => 'Kelompok B', 'jam_per_minggu' => 2, 'urutan' => 9],
            ['kode' => 'PKWU', 'nama' => 'Prakarya dan Kewirausahaan', 'singkatan' => 'PKWU', 'kelompok' => 'Kelompok B', 'jam_per_minggu' => 2, 'urutan' => 10],

            // Muatan Lokal
            ['kode' => 'ARAB', 'nama' => 'Bahasa Arab', 'singkatan' => 'ARAB', 'kelompok' => 'Muatan Lokal', 'jam_per_minggu' => 2, 'urutan' => 11],
            ['kode' => 'TAHFIDZ', 'nama' => 'Tahfidz Al-Quran', 'singkatan' => 'TFZ', 'kelompok' => 'Muatan Lokal', 'jam_per_minggu' => 4, 'urutan' => 12],
            ['kode' => 'FIQIH', 'nama' => 'Fiqih', 'singkatan' => 'FQH', 'kelompok' => 'Muatan Lokal', 'jam_per_minggu' => 2, 'urutan' => 13],
            ['kode' => 'AQIDAH', 'nama' => 'Aqidah Akhlak', 'singkatan' => 'AQD', 'kelompok' => 'Muatan Lokal', 'jam_per_minggu' => 2, 'urutan' => 14],
            ['kode' => 'SKI', 'nama' => 'Sejarah Kebudayaan Islam', 'singkatan' => 'SKI', 'kelompok' => 'Muatan Lokal', 'jam_per_minggu' => 2, 'urutan' => 15],
        ];

        foreach ($mataPelajarans as $data) {
            MataPelajaran::firstOrCreate(
                ['kode' => $data['kode']],
                array_merge($data, [
                    'kkm' => 75,
                    'is_active' => true,
                ])
            );
        }
    }
}
