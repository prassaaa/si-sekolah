<?php

namespace Database\Seeders;

use App\Models\Ruangan;
use Illuminate\Database\Seeder;

class RuanganSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ruangans = [
            // Ruang Kelas
            ['kode' => 'RK-701', 'nama' => 'Ruang Kelas 7A', 'jenis' => 'kelas', 'gedung' => 'Gedung A', 'lantai' => 1, 'kapasitas' => 32],
            ['kode' => 'RK-702', 'nama' => 'Ruang Kelas 7B', 'jenis' => 'kelas', 'gedung' => 'Gedung A', 'lantai' => 1, 'kapasitas' => 32],
            ['kode' => 'RK-703', 'nama' => 'Ruang Kelas 7C', 'jenis' => 'kelas', 'gedung' => 'Gedung A', 'lantai' => 2, 'kapasitas' => 32],
            ['kode' => 'RK-704', 'nama' => 'Ruang Kelas 7D', 'jenis' => 'kelas', 'gedung' => 'Gedung A', 'lantai' => 2, 'kapasitas' => 32],
            ['kode' => 'RK-801', 'nama' => 'Ruang Kelas 8A', 'jenis' => 'kelas', 'gedung' => 'Gedung B', 'lantai' => 1, 'kapasitas' => 32],
            ['kode' => 'RK-802', 'nama' => 'Ruang Kelas 8B', 'jenis' => 'kelas', 'gedung' => 'Gedung B', 'lantai' => 1, 'kapasitas' => 32],
            ['kode' => 'RK-803', 'nama' => 'Ruang Kelas 8C', 'jenis' => 'kelas', 'gedung' => 'Gedung B', 'lantai' => 2, 'kapasitas' => 32],
            ['kode' => 'RK-804', 'nama' => 'Ruang Kelas 8D', 'jenis' => 'kelas', 'gedung' => 'Gedung B', 'lantai' => 2, 'kapasitas' => 32],
            ['kode' => 'RK-901', 'nama' => 'Ruang Kelas 9A', 'jenis' => 'kelas', 'gedung' => 'Gedung C', 'lantai' => 1, 'kapasitas' => 32],
            ['kode' => 'RK-902', 'nama' => 'Ruang Kelas 9B', 'jenis' => 'kelas', 'gedung' => 'Gedung C', 'lantai' => 1, 'kapasitas' => 32],
            ['kode' => 'RK-903', 'nama' => 'Ruang Kelas 9C', 'jenis' => 'kelas', 'gedung' => 'Gedung C', 'lantai' => 2, 'kapasitas' => 32],
            ['kode' => 'RK-904', 'nama' => 'Ruang Kelas 9D', 'jenis' => 'kelas', 'gedung' => 'Gedung C', 'lantai' => 2, 'kapasitas' => 32],

            // Laboratorium
            ['kode' => 'LAB-IPA', 'nama' => 'Laboratorium IPA', 'jenis' => 'lab', 'gedung' => 'Gedung D', 'lantai' => 1, 'kapasitas' => 36, 'keterangan' => 'Laboratorium sains terpadu (fisika, kimia, biologi)'],
            ['kode' => 'LAB-KOM', 'nama' => 'Laboratorium Komputer', 'jenis' => 'lab', 'gedung' => 'Gedung D', 'lantai' => 2, 'kapasitas' => 40, 'keterangan' => 'Lab komputer dengan 40 unit PC'],
            ['kode' => 'LAB-BHS', 'nama' => 'Laboratorium Bahasa', 'jenis' => 'lab', 'gedung' => 'Gedung D', 'lantai' => 2, 'kapasitas' => 32, 'keterangan' => 'Lab bahasa dengan headset multimedia'],

            // Kantor
            ['kode' => 'KTR-KPS', 'nama' => 'Ruang Kepala Sekolah', 'jenis' => 'kantor', 'gedung' => 'Gedung Utama', 'lantai' => 1, 'kapasitas' => 10],
            ['kode' => 'KTR-GRU', 'nama' => 'Ruang Guru', 'jenis' => 'kantor', 'gedung' => 'Gedung Utama', 'lantai' => 1, 'kapasitas' => 40],
            ['kode' => 'KTR-TU', 'nama' => 'Ruang Tata Usaha', 'jenis' => 'kantor', 'gedung' => 'Gedung Utama', 'lantai' => 1, 'kapasitas' => 15],
            ['kode' => 'KTR-BK', 'nama' => 'Ruang Bimbingan Konseling', 'jenis' => 'kantor', 'gedung' => 'Gedung Utama', 'lantai' => 2, 'kapasitas' => 8],

            // Gudang
            ['kode' => 'GDG-01', 'nama' => 'Gudang Umum', 'jenis' => 'gudang', 'gedung' => 'Gedung Belakang', 'lantai' => 1, 'kapasitas' => null, 'keterangan' => 'Penyimpanan barang umum dan arsip lama'],
            ['kode' => 'GDG-02', 'nama' => 'Gudang Olahraga', 'jenis' => 'gudang', 'gedung' => 'Gedung Olahraga', 'lantai' => 1, 'kapasitas' => null, 'keterangan' => 'Penyimpanan peralatan olahraga'],

            // Perpustakaan
            ['kode' => 'PRP-01', 'nama' => 'Perpustakaan', 'jenis' => 'perpustakaan', 'gedung' => 'Gedung Utama', 'lantai' => 2, 'kapasitas' => 60, 'keterangan' => 'Perpustakaan sekolah dengan koleksi ±5.000 judul buku'],

            // Aula
            ['kode' => 'AUL-01', 'nama' => 'Aula Serbaguna', 'jenis' => 'aula', 'gedung' => 'Gedung Aula', 'lantai' => 1, 'kapasitas' => 500, 'keterangan' => 'Aula utama untuk kegiatan sekolah dan pertemuan wali murid'],
        ];

        foreach ($ruangans as $data) {
            Ruangan::firstOrCreate(
                ['kode' => $data['kode']],
                array_merge(['penanggung_jawab_id' => null, 'keterangan' => null, 'is_active' => true], $data)
            );
        }

        $this->command->info('Ruangan seeded: '.count($ruangans).' ruangan.');
    }
}
