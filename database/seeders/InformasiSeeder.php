<?php

namespace Database\Seeders;

use App\Models\Informasi;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class InformasiSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        $informasis = [
            [
                'judul' => 'Pengumuman Penerimaan Siswa Baru',
                'slug' => Str::slug('Pengumuman Penerimaan Siswa Baru'),
                'ringkasan' => 'Pendaftaran siswa baru tahun ajaran 2026/2027 telah dibuka.',
                'konten' => 'Pendaftaran siswa baru tahun ajaran 2026/2027 telah dibuka. Silakan kunjungi website resmi sekolah untuk informasi lebih lanjut.',
                'kategori' => 'Pengumuman',
                'prioritas' => 'Tinggi',
                'tanggal_publish' => now(),
                'tanggal_expired' => now()->addMonths(3),
                'is_published' => true,
                'is_pinned' => true,
                'created_by' => $admin?->id,
                'views_count' => 0,
            ],
            [
                'judul' => 'Jadwal Ujian Semester Ganjil',
                'slug' => Str::slug('Jadwal Ujian Semester Ganjil'),
                'ringkasan' => 'Ujian Semester Ganjil akan dilaksanakan pada tanggal 10-20 Desember 2026.',
                'konten' => 'Ujian Semester Ganjil akan dilaksanakan pada tanggal 10-20 Desember 2026. Harap siswa mempersiapkan diri dengan baik.',
                'kategori' => 'Kegiatan',
                'prioritas' => 'Normal',
                'tanggal_publish' => now(),
                'tanggal_expired' => now()->addMonths(1),
                'is_published' => true,
                'is_pinned' => false,
                'created_by' => $admin?->id,
                'views_count' => 0,
            ],
            [
                'judul' => 'Libur Hari Raya Idul Fitri',
                'slug' => Str::slug('Libur Hari Raya Idul Fitri'),
                'ringkasan' => 'Sekolah akan libur dalam rangka Hari Raya Idul Fitri 1447 H.',
                'konten' => 'Sekolah akan libur dalam rangka Hari Raya Idul Fitri 1447 H. Libur berlangsung selama 2 minggu.',
                'kategori' => 'Pengumuman',
                'prioritas' => 'Normal',
                'tanggal_publish' => now()->addMonths(2),
                'tanggal_expired' => now()->addMonths(2)->addWeeks(2),
                'is_published' => true,
                'is_pinned' => false,
                'created_by' => $admin?->id,
                'views_count' => 0,
            ],
        ];

        foreach ($informasis as $info) {
            Informasi::firstOrCreate(['judul' => $info['judul']], $info);
        }
    }
}
