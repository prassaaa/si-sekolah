<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Pegawai;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class KelasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tahunAjaran = TahunAjaran::where('is_active', true)->first();

        if (! $tahunAjaran) {
            $tahunAjaran = TahunAjaran::first();
        }

        if (! $tahunAjaran) {
            $this->command->warn('Tidak ada tahun ajaran. Jalankan TahunAjaranSeeder terlebih dahulu.');

            return;
        }

        // Ambil pegawai guru untuk wali kelas
        $gurus = Pegawai::whereHas('jabatan', function ($q) {
            $q->where('nama', 'like', '%Guru%');
        })->pluck('id')->toArray();

        $kelasData = $this->getKelasData();
        $guruIndex = 0;

        foreach ($kelasData as $data) {
            // Cek apakah kelas sudah ada
            $exists = Kelas::where('tahun_ajaran_id', $tahunAjaran->id)
                ->where('nama', $data['nama'])
                ->exists();

            if ($exists) {
                continue;
            }

            $waliKelasId = null;
            if (count($gurus) > 0) {
                $waliKelasId = $gurus[$guruIndex % count($gurus)];
            }

            Kelas::create([
                'tahun_ajaran_id' => $tahunAjaran->id,
                'nama' => $data['nama'],
                'tingkat' => $data['tingkat'],
                'jurusan' => $data['jurusan'] ?? null,
                'wali_kelas_id' => $waliKelasId,
                'kapasitas' => $data['kapasitas'] ?? 32,
                'ruangan' => $data['ruangan'] ?? 'Ruang '.$data['nama'],
                'urutan' => $data['urutan'],
                'is_active' => true,
                'keterangan' => null,
            ]);

            $guruIndex++;
        }

        $this->command->info('Kelas seeded successfully for '.$tahunAjaran->nama);
    }

    /**
     * Data kelas yang akan di-seed
     *
     * @return array<int, array{nama: string, tingkat: int, jurusan?: string, kapasitas?: int, ruangan?: string, urutan: int}>
     */
    private function getKelasData(): array
    {
        return [
            // Kelas 7
            ['nama' => '7A', 'tingkat' => 7, 'kapasitas' => 32, 'ruangan' => 'Ruang 7A', 'urutan' => 1],
            ['nama' => '7B', 'tingkat' => 7, 'kapasitas' => 32, 'ruangan' => 'Ruang 7B', 'urutan' => 2],
            ['nama' => '7C', 'tingkat' => 7, 'kapasitas' => 32, 'ruangan' => 'Ruang 7C', 'urutan' => 3],
            ['nama' => '7D', 'tingkat' => 7, 'kapasitas' => 32, 'ruangan' => 'Ruang 7D', 'urutan' => 4],

            // Kelas 8
            ['nama' => '8A', 'tingkat' => 8, 'kapasitas' => 32, 'ruangan' => 'Ruang 8A', 'urutan' => 5],
            ['nama' => '8B', 'tingkat' => 8, 'kapasitas' => 32, 'ruangan' => 'Ruang 8B', 'urutan' => 6],
            ['nama' => '8C', 'tingkat' => 8, 'kapasitas' => 32, 'ruangan' => 'Ruang 8C', 'urutan' => 7],
            ['nama' => '8D', 'tingkat' => 8, 'kapasitas' => 32, 'ruangan' => 'Ruang 8D', 'urutan' => 8],

            // Kelas 9
            ['nama' => '9A', 'tingkat' => 9, 'kapasitas' => 32, 'ruangan' => 'Ruang 9A', 'urutan' => 9],
            ['nama' => '9B', 'tingkat' => 9, 'kapasitas' => 32, 'ruangan' => 'Ruang 9B', 'urutan' => 10],
            ['nama' => '9C', 'tingkat' => 9, 'kapasitas' => 32, 'ruangan' => 'Ruang 9C', 'urutan' => 11],
            ['nama' => '9D', 'tingkat' => 9, 'kapasitas' => 32, 'ruangan' => 'Ruang 9D', 'urutan' => 12],
        ];
    }
}
