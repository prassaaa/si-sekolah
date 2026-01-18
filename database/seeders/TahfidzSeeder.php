<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\Tahfidz;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TahfidzSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siswas = Siswa::where('is_active', true)
            ->whereNull('deleted_at')
            ->take(60)
            ->get();

        $semester = Semester::where('is_active', true)->first();

        // Gunakan pegawai aktif manapun sebagai penguji (bukan hanya guru)
        $pengujis = Pegawai::where('is_active', true)->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Skipping TahfidzSeeder: Tidak ada siswa aktif.');

            return;
        }

        if (! $semester) {
            $this->command->warn('Skipping TahfidzSeeder: Tidak ada semester aktif.');

            return;
        }

        if ($pengujis->isEmpty()) {
            $this->command->warn('Skipping TahfidzSeeder: Tidak ada penguji (pegawai).');

            return;
        }

        // Daftar surah dengan jumlah ayat
        $surahList = [
            // Juz 30 (Juz Amma)
            ['nama' => 'Al-Fatihah', 'ayat' => 7, 'juz' => 1],
            ['nama' => 'An-Naba', 'ayat' => 40, 'juz' => 30],
            ['nama' => 'An-Naziat', 'ayat' => 46, 'juz' => 30],
            ['nama' => 'Abasa', 'ayat' => 42, 'juz' => 30],
            ['nama' => 'At-Takwir', 'ayat' => 29, 'juz' => 30],
            ['nama' => 'Al-Infitar', 'ayat' => 19, 'juz' => 30],
            ['nama' => 'Al-Mutaffifin', 'ayat' => 36, 'juz' => 30],
            ['nama' => 'Al-Insyiqaq', 'ayat' => 25, 'juz' => 30],
            ['nama' => 'Al-Buruj', 'ayat' => 22, 'juz' => 30],
            ['nama' => 'At-Tariq', 'ayat' => 17, 'juz' => 30],
            ['nama' => 'Al-Ala', 'ayat' => 19, 'juz' => 30],
            ['nama' => 'Al-Gasyiyah', 'ayat' => 26, 'juz' => 30],
            ['nama' => 'Al-Fajr', 'ayat' => 30, 'juz' => 30],
            ['nama' => 'Al-Balad', 'ayat' => 20, 'juz' => 30],
            ['nama' => 'Asy-Syams', 'ayat' => 15, 'juz' => 30],
            ['nama' => 'Al-Lail', 'ayat' => 21, 'juz' => 30],
            ['nama' => 'Ad-Duha', 'ayat' => 11, 'juz' => 30],
            ['nama' => 'Asy-Syarh', 'ayat' => 8, 'juz' => 30],
            ['nama' => 'At-Tin', 'ayat' => 8, 'juz' => 30],
            ['nama' => 'Al-Alaq', 'ayat' => 19, 'juz' => 30],
            ['nama' => 'Al-Qadr', 'ayat' => 5, 'juz' => 30],
            ['nama' => 'Al-Bayyinah', 'ayat' => 8, 'juz' => 30],
            ['nama' => 'Az-Zalzalah', 'ayat' => 8, 'juz' => 30],
            ['nama' => 'Al-Adiyat', 'ayat' => 11, 'juz' => 30],
            ['nama' => 'Al-Qariah', 'ayat' => 11, 'juz' => 30],
            ['nama' => 'At-Takasur', 'ayat' => 8, 'juz' => 30],
            ['nama' => 'Al-Asr', 'ayat' => 3, 'juz' => 30],
            ['nama' => 'Al-Humazah', 'ayat' => 9, 'juz' => 30],
            ['nama' => 'Al-Fil', 'ayat' => 5, 'juz' => 30],
            ['nama' => 'Quraisy', 'ayat' => 4, 'juz' => 30],
            ['nama' => 'Al-Maun', 'ayat' => 7, 'juz' => 30],
            ['nama' => 'Al-Kausar', 'ayat' => 3, 'juz' => 30],
            ['nama' => 'Al-Kafirun', 'ayat' => 6, 'juz' => 30],
            ['nama' => 'An-Nasr', 'ayat' => 3, 'juz' => 30],
            ['nama' => 'Al-Lahab', 'ayat' => 5, 'juz' => 30],
            ['nama' => 'Al-Ikhlas', 'ayat' => 4, 'juz' => 30],
            ['nama' => 'Al-Falaq', 'ayat' => 5, 'juz' => 30],
            ['nama' => 'An-Nas', 'ayat' => 6, 'juz' => 30],
        ];

        $jenisList = ['setoran', 'murojaah', 'ujian'];
        $statusList = ['lulus', 'mengulang', 'pending'];

        $tahfidzData = [];
        $today = Carbon::now();

        foreach ($siswas as $siswa) {
            // Setiap siswa punya 3-8 catatan tahfidz dalam 6 bulan terakhir
            $count = rand(3, 8);

            for ($i = 0; $i < $count; $i++) {
                $surah = $surahList[array_rand($surahList)];
                $maxAyat = $surah['ayat'];

                // Ayat mulai dan selesai
                $ayatMulai = rand(1, max(1, $maxAyat - 5));
                $ayatSelesai = min($ayatMulai + rand(3, 10), $maxAyat);
                $jumlahAyat = $ayatSelesai - $ayatMulai + 1;

                // Jenis dan status
                $jenis = $jenisList[array_rand($jenisList)];

                // Status dengan distribusi realistis
                $statusRand = rand(1, 100);
                if ($statusRand <= 60) {
                    $status = 'lulus';
                } elseif ($statusRand <= 85) {
                    $status = 'mengulang';
                } else {
                    $status = 'pending';
                }

                // Nilai berdasarkan status
                if ($status === 'lulus') {
                    $nilai = rand(75, 100);
                } elseif ($status === 'mengulang') {
                    $nilai = rand(50, 74);
                } else {
                    $nilai = null;
                }

                // Tanggal dalam 6 bulan terakhir
                $tanggal = $today->copy()->subDays(rand(1, 180));

                // Skip hari Minggu
                if ($tanggal->isSunday()) {
                    $tanggal = $tanggal->subDay();
                }

                $catatanList = [
                    null,
                    'Bacaan sudah lancar',
                    'Perlu memperbaiki tajwid',
                    'Makhraj perlu diperbaiki',
                    'Hafalan sudah kuat',
                    'Perlu lebih banyak murojaah',
                    'Sangat baik, lanjutkan!',
                    'Panjang pendek perlu diperhatikan',
                ];

                $tahfidzData[] = [
                    'siswa_id' => $siswa->id,
                    'semester_id' => $semester->id,
                    'penguji_id' => $pengujis->random()->id,
                    'surah' => $surah['nama'],
                    'ayat_mulai' => $ayatMulai,
                    'ayat_selesai' => $ayatSelesai,
                    'jumlah_ayat' => $jumlahAyat,
                    'juz' => $surah['juz'],
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'jenis' => $jenis,
                    'status' => $status,
                    'nilai' => $nilai,
                    'catatan' => $catatanList[array_rand($catatanList)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Insert in chunks to avoid memory issues
        foreach (array_chunk($tahfidzData, 100) as $chunk) {
            Tahfidz::insert($chunk);
        }

        $this->command->info('TahfidzSeeder: Created '.count($tahfidzData).' records.');
    }
}
