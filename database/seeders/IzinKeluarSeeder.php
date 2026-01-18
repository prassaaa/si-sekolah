<?php

namespace Database\Seeders;

use App\Models\IzinKeluar;
use App\Models\Pegawai;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class IzinKeluarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $siswas = Siswa::where('is_active', true)
            ->whereNull('deleted_at')
            ->inRandomOrder()
            ->take(30)
            ->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Tidak ada siswa aktif. Silakan jalankan SiswaSeeder terlebih dahulu.');

            return;
        }

        $petugas = Pegawai::first();

        $keperluanList = [
            'Kontrol ke dokter',
            'Mengurus KTP',
            'Keperluan keluarga mendesak',
            'Sakit perlu pulang',
            'Mengikuti lomba di luar sekolah',
            'Tes kesehatan',
            'Ambil obat di apotek',
            'Periksa gigi',
            'Urusan administrasi',
            'Menjemput keluarga',
        ];

        $tujuanList = [
            'Rumah Sakit',
            'Puskesmas',
            'Kantor Kecamatan',
            'Rumah',
            'Klinik',
            'Apotek',
            'Kantor Dinas',
            'Bank',
        ];

        $hubunganList = ['Ayah', 'Ibu', 'Kakak', 'Paman', 'Bibi', 'Wali', 'Supir keluarga'];

        $counter = 0;
        $today = Carbon::now();

        foreach ($siswas as $siswa) {
            // Buat 1-3 izin keluar per siswa dalam 2 bulan terakhir
            $jumlahIzin = rand(1, 3);

            for ($i = 0; $i < $jumlahIzin; $i++) {
                $tanggal = $today->copy()->subDays(rand(1, 60));

                // Skip hari Sabtu dan Minggu
                if ($tanggal->isWeekend()) {
                    continue;
                }

                $jamKeluar = sprintf('%02d:%02d', rand(8, 11), rand(0, 59));
                $sudahKembali = rand(1, 10) <= 7; // 70% sudah kembali
                $jamKembali = $sudahKembali ? sprintf('%02d:%02d', rand(12, 15), rand(0, 59)) : null;

                // Status berdasarkan logika
                $statusOptions = ['diizinkan', 'ditolak', 'pending'];
                if ($tanggal->lt($today->copy()->subDays(3))) {
                    // Izin lama - sudah diproses
                    $status = rand(1, 10) <= 9 ? 'diizinkan' : 'ditolak';
                } else {
                    // Izin baru - mungkin masih pending
                    $status = $statusOptions[array_rand($statusOptions)];
                }

                IzinKeluar::create([
                    'siswa_id' => $siswa->id,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'jam_keluar' => $jamKeluar,
                    'jam_kembali' => $status === 'diizinkan' ? $jamKembali : null,
                    'keperluan' => $keperluanList[array_rand($keperluanList)],
                    'tujuan' => $tujuanList[array_rand($tujuanList)],
                    'penjemput_nama' => fake('id_ID')->name(),
                    'penjemput_hubungan' => $hubunganList[array_rand($hubunganList)],
                    'penjemput_telepon' => fake('id_ID')->phoneNumber(),
                    'petugas_id' => $status !== 'pending' ? $petugas?->id : null,
                    'status' => $status,
                    'catatan' => $status === 'ditolak' ? 'Tidak ada penjemput yang sesuai' : null,
                ]);

                $counter++;
            }
        }

        $this->command->info("IzinKeluar seeded successfully: {$counter} records");
    }
}
