<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahfidz;
use Illuminate\Database\Seeder;

class TahfidzSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siswas = Siswa::limit(10)->get();
        $semester = Semester::first();
        $pengujis = Pegawai::guru()->active()->limit(3)->get();

        if ($siswas->isEmpty() || ! $semester || $pengujis->isEmpty()) {
            $this->command->warn('Skipping TahfidzSeeder: Required data (Siswa, Semester, or Penguji) not found.');

            return;
        }

        // Juz Amma surah list for realistic data
        $juzAmmaSurah = [
            'Al-Fatihah', 'An-Naba\'', 'An-Nazi\'at', 'Abasa', 'At-Takwir',
            'Al-Infitar', 'Al-Mutaffifin', 'Al-Insyiqaq', 'Al-Buruj', 'At-Tariq',
            'Al-A\'la', 'Al-Gasyiyah', 'Al-Fajr', 'Al-Balad', 'Asy-Syams',
            'Al-Lail', 'Ad-Duha', 'Asy-Syarh', 'At-Tin', 'Al-Alaq',
        ];

        $tahfidzData = [];

        foreach ($siswas as $siswa) {
            // Each student has 2-5 tahfidz records
            $count = fake()->numberBetween(2, 5);

            for ($i = 0; $i < $count; $i++) {
                $surah = fake()->randomElement($juzAmmaSurah);
                $ayatMulai = fake()->numberBetween(1, 10);
                $ayatSelesai = $ayatMulai + fake()->numberBetween(3, 10);
                $jenis = fake()->randomElement(['setoran', 'murojaah', 'ujian']);
                $status = fake()->randomElement(['lulus', 'mengulang', 'pending']);

                $tahfidzData[] = [
                    'siswa_id' => $siswa->id,
                    'semester_id' => $semester->id,
                    'penguji_id' => $pengujis->random()->id,
                    'surah' => $surah,
                    'ayat_mulai' => $ayatMulai,
                    'ayat_selesai' => $ayatSelesai,
                    'jumlah_ayat' => $ayatSelesai - $ayatMulai + 1,
                    'juz' => 30,
                    'tanggal' => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d'),
                    'jenis' => $jenis,
                    'status' => $status,
                    'nilai' => $status === 'pending' ? null : fake()->numberBetween(60, 100),
                    'catatan' => fake()->optional(0.3)->sentence(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Tahfidz::insert($tahfidzData);

        $this->command->info('TahfidzSeeder: Created '.count($tahfidzData).' records.');
    }
}
