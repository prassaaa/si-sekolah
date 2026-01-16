<?php

namespace Database\Seeders;

use App\Models\Kelas;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil semua kelas aktif
        $kelasAktif = Kelas::where('is_active', true)->get();

        if ($kelasAktif->isEmpty()) {
            $this->command->warn('Tidak ada kelas aktif. Jalankan KelasSeeder terlebih dahulu.');

            return;
        }

        // Untuk setiap kelas, buat beberapa siswa
        foreach ($kelasAktif as $kelas) {
            // Skip jika sudah ada siswa di kelas ini
            if (Siswa::where('kelas_id', $kelas->id)->exists()) {
                continue;
            }

            // Buat 20-30 siswa per kelas
            $jumlahSiswa = min(rand(20, 30), $kelas->kapasitas);

            Siswa::factory()
                ->count($jumlahSiswa)
                ->forKelas($kelas)
                ->create([
                    'tahun_masuk' => $kelas->tahunAjaran->tahun_mulai ?? now()->year,
                ]);

            $this->command->info("Created {$jumlahSiswa} siswa for {$kelas->nama}");
        }

        $this->command->info('Siswa seeded successfully');
    }
}
