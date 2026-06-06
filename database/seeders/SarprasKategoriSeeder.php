<?php

namespace Database\Seeders;

use App\Models\SarprasKategori;
use Illuminate\Database\Seeder;

class SarprasKategoriSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategoris = [
            [
                'kode' => 'ELK',
                'nama' => 'Elektronik',
                'deskripsi' => 'Perangkat elektronik seperti komputer, proyektor, dan peralatan audio-visual.',
                'is_active' => true,
            ],
            [
                'kode' => 'MBL',
                'nama' => 'Meubelair',
                'deskripsi' => 'Perabot sekolah seperti meja, kursi, lemari, dan rak.',
                'is_active' => true,
            ],
            [
                'kode' => 'KDR',
                'nama' => 'Kendaraan',
                'deskripsi' => 'Kendaraan operasional sekolah seperti mobil dan sepeda motor.',
                'is_active' => true,
            ],
            [
                'kode' => 'LAB',
                'nama' => 'Alat Lab',
                'deskripsi' => 'Peralatan laboratorium IPA, kimia, fisika, dan biologi.',
                'is_active' => true,
            ],
            [
                'kode' => 'OLR',
                'nama' => 'Alat Olahraga',
                'deskripsi' => 'Perlengkapan olahraga dan pendidikan jasmani.',
                'is_active' => true,
            ],
            [
                'kode' => 'BKM',
                'nama' => 'Buku/Media',
                'deskripsi' => 'Buku pelajaran, buku referensi, media pembelajaran, dan alat peraga.',
                'is_active' => true,
            ],
        ];

        foreach ($kategoris as $data) {
            SarprasKategori::firstOrCreate(
                ['kode' => $data['kode']],
                $data
            );
        }

        $this->command->info('SarprasKategori seeded: '.count($kategoris).' kategori.');
    }
}
