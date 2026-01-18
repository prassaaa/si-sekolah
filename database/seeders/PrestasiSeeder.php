<?php

namespace Database\Seeders;

use App\Models\Prestasi;
use App\Models\Semester;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PrestasiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $semester = Semester::where('is_active', true)->first();

        if (! $semester) {
            $this->command->warn('Tidak ada semester aktif. Silakan jalankan SemesterSeeder terlebih dahulu.');

            return;
        }

        $siswas = Siswa::where('is_active', true)
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->take(30)
            ->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Tidak ada siswa aktif. Silakan jalankan SiswaSeeder terlebih dahulu.');

            return;
        }

        // Definisi prestasi berdasarkan jenis
        $prestasiData = [
            'akademik' => [
                ['nama' => 'Olimpiade Matematika', 'penyelenggara' => 'Dinas Pendidikan Kota'],
                ['nama' => 'Olimpiade Sains', 'penyelenggara' => 'Dinas Pendidikan Provinsi'],
                ['nama' => 'Lomba Karya Tulis Ilmiah', 'penyelenggara' => 'Universitas Negeri'],
                ['nama' => 'Olimpiade Bahasa Inggris', 'penyelenggara' => 'British Council'],
                ['nama' => 'Olimpiade Bahasa Indonesia', 'penyelenggara' => 'Balai Bahasa'],
                ['nama' => 'Kompetisi Robotika', 'penyelenggara' => 'ITB'],
                ['nama' => 'Lomba Debat Bahasa Inggris', 'penyelenggara' => 'British Embassy'],
                ['nama' => 'Cerdas Cermat', 'penyelenggara' => 'Kemendikbud'],
            ],
            'non_akademik' => [
                ['nama' => 'Lomba Futsal', 'penyelenggara' => 'KONI Daerah'],
                ['nama' => 'Lomba Bulu Tangkis', 'penyelenggara' => 'PBSI Daerah'],
                ['nama' => 'Lomba Renang', 'penyelenggara' => 'PRSI'],
                ['nama' => 'Lomba Pencak Silat', 'penyelenggara' => 'IPSI'],
                ['nama' => 'Lomba Pramuka', 'penyelenggara' => 'Kwartir Daerah'],
                ['nama' => 'Lomba Paduan Suara', 'penyelenggara' => 'Dinas Kebudayaan'],
                ['nama' => 'Lomba Tari Tradisional', 'penyelenggara' => 'Taman Budaya'],
                ['nama' => 'Lomba Hafalan Al-Quran', 'penyelenggara' => 'Kemenag'],
                ['nama' => 'Lomba Kaligrafi', 'penyelenggara' => 'Masjid Agung'],
                ['nama' => 'Lomba Pidato', 'penyelenggara' => 'Kantor Bahasa'],
                ['nama' => 'Lomba Fotografi', 'penyelenggara' => 'Komunitas Fotografi'],
                ['nama' => 'Lomba Film Pendek', 'penyelenggara' => 'Festival Film Pelajar'],
            ],
        ];

        $tingkatList = ['sekolah', 'kecamatan', 'kabupaten', 'provinsi', 'nasional', 'internasional'];
        $peringkatList = ['juara_1', 'juara_2', 'juara_3', 'harapan_1', 'peserta'];

        $counter = 0;
        $today = Carbon::now();

        foreach ($siswas as $siswa) {
            // Tidak semua siswa punya prestasi, dan yang punya bisa 1-2 prestasi
            if (rand(1, 100) > 40) {
                continue; // 60% siswa tidak ada prestasi
            }

            $jumlahPrestasi = rand(1, 2);

            for ($i = 0; $i < $jumlahPrestasi; $i++) {
                $jenis = array_rand($prestasiData);
                $prestasi = $prestasiData[$jenis][array_rand($prestasiData[$jenis])];

                $tanggal = $today->copy()->subDays(rand(30, 365));

                // Tingkat dan peringkat dengan distribusi realistis
                $tingkatRand = rand(1, 100);
                if ($tingkatRand <= 40) {
                    $tingkat = 'sekolah';
                } elseif ($tingkatRand <= 60) {
                    $tingkat = 'kecamatan';
                } elseif ($tingkatRand <= 80) {
                    $tingkat = 'kabupaten';
                } elseif ($tingkatRand <= 95) {
                    $tingkat = 'provinsi';
                } else {
                    $tingkat = 'nasional';
                }

                // Peringkat dengan distribusi realistis
                $peringkatRand = rand(1, 100);
                if ($peringkatRand <= 15) {
                    $peringkat = 'juara_1';
                } elseif ($peringkatRand <= 35) {
                    $peringkat = 'juara_2';
                } elseif ($peringkatRand <= 55) {
                    $peringkat = 'juara_3';
                } elseif ($peringkatRand <= 75) {
                    $peringkat = 'harapan_1';
                } else {
                    $peringkat = 'peserta';
                }

                Prestasi::create([
                    'siswa_id' => $siswa->id,
                    'semester_id' => $semester->id,
                    'nama_prestasi' => $prestasi['nama'],
                    'tingkat' => $tingkat,
                    'jenis' => $jenis,
                    'peringkat' => $peringkat,
                    'penyelenggara' => $prestasi['penyelenggara'],
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'bukti' => null,
                    'keterangan' => 'Mengikuti '.strtolower($prestasi['nama']).' tingkat '.$tingkat,
                ]);

                $counter++;
            }
        }

        $this->command->info("Prestasi seeded successfully: {$counter} records");
    }
}
