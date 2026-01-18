<?php

namespace Database\Seeders;

use App\Models\IzinPulang;
use App\Models\Pegawai;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IzinPulangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siswas = Siswa::where('is_active', true)
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->take(25)
            ->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Tidak ada siswa aktif. Silakan jalankan SiswaSeeder terlebih dahulu.');

            return;
        }

        $petugas = Pegawai::first();

        $alasanList = [
            'Sakit perut',
            'Demam tinggi',
            'Pusing/mual',
            'Keperluan keluarga mendesak',
            'Ada acara keluarga',
            'Kontrol ke dokter',
            'Anggota keluarga sakit',
            'Kecelakaan ringan',
            'Harus menjemput adik',
            'Ada tamu penting dari luar kota',
        ];

        $kategoriList = ['sakit', 'kepentingan_keluarga', 'urusan_pribadi', 'lainnya'];

        $hubunganList = ['Ayah', 'Ibu', 'Kakak', 'Paman', 'Bibi', 'Wali', 'Supir keluarga'];

        $counter = 0;
        $today = Carbon::now();

        foreach ($siswas as $siswa) {
            // Buat 1-2 izin pulang per siswa dalam 2 bulan terakhir
            $jumlahIzin = rand(1, 2);

            for ($i = 0; $i < $jumlahIzin; $i++) {
                $tanggal = $today->copy()->subDays(rand(1, 60));

                // Skip hari Sabtu dan Minggu
                if ($tanggal->isWeekend()) {
                    continue;
                }

                $alasan = $alasanList[array_rand($alasanList)];

                // Tentukan kategori berdasarkan alasan
                if (str_contains(strtolower($alasan), 'sakit') || str_contains(strtolower($alasan), 'demam') || str_contains(strtolower($alasan), 'pusing')) {
                    $kategori = 'sakit';
                } elseif (str_contains(strtolower($alasan), 'keluarga')) {
                    $kategori = 'kepentingan_keluarga';
                } else {
                    $kategori = $kategoriList[array_rand($kategoriList)];
                }

                // Status berdasarkan tanggal
                if ($tanggal->lt($today->copy()->subDays(2))) {
                    $status = rand(1, 10) <= 9 ? 'diizinkan' : 'ditolak';
                } else {
                    $statusOptions = ['diizinkan', 'ditolak', 'pending'];
                    $status = $statusOptions[array_rand($statusOptions)];
                }

                $jamPulang = sprintf('%02d:%02d', rand(9, 13), rand(0, 59));

                IzinPulang::create([
                    'siswa_id' => $siswa->id,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'jam_pulang' => $jamPulang,
                    'alasan' => $alasan,
                    'kategori' => $kategori,
                    'penjemput_nama' => fake('id_ID')->name(),
                    'penjemput_hubungan' => $hubunganList[array_rand($hubunganList)],
                    'penjemput_telepon' => fake('id_ID')->phoneNumber(),
                    'petugas_id' => $status !== 'pending' ? $petugas?->id : null,
                    'status' => $status,
                    'catatan' => $status === 'ditolak' ? 'Alasan tidak cukup mendesak' : null,
                ]);

                $counter++;
            }
        }

        $this->command->info("IzinPulang seeded successfully: {$counter} records");
    }
}
