<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\Pelanggaran;
use App\Models\Semester;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PelanggaranSeeder extends Seeder
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
            ->take(40)
            ->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Tidak ada siswa aktif. Silakan jalankan SiswaSeeder terlebih dahulu.');

            return;
        }

        $pelapor = Pegawai::first();

        // Definisi pelanggaran berdasarkan kategori
        $pelanggaranData = [
            'ringan' => [
                ['jenis' => 'Terlambat masuk sekolah', 'poin' => 5, 'tindak_lanjut' => 'Peringatan lisan'],
                ['jenis' => 'Tidak membawa buku pelajaran', 'poin' => 5, 'tindak_lanjut' => 'Peringatan lisan'],
                ['jenis' => 'Seragam tidak lengkap', 'poin' => 5, 'tindak_lanjut' => 'Peringatan lisan'],
                ['jenis' => 'Tidak mengerjakan PR', 'poin' => 5, 'tindak_lanjut' => 'Peringatan lisan'],
                ['jenis' => 'Rambut panjang (putra)', 'poin' => 5, 'tindak_lanjut' => 'Diberi waktu 3 hari untuk potong rambut'],
                ['jenis' => 'Tidak memakai atribut lengkap', 'poin' => 5, 'tindak_lanjut' => 'Peringatan lisan'],
            ],
            'sedang' => [
                ['jenis' => 'Bolos pelajaran', 'poin' => 15, 'tindak_lanjut' => 'Surat peringatan ke orang tua'],
                ['jenis' => 'Menyontek saat ujian', 'poin' => 20, 'tindak_lanjut' => 'Nilai ujian dikurangi 50%'],
                ['jenis' => 'Membawa HP tanpa izin', 'poin' => 15, 'tindak_lanjut' => 'HP disita 1 minggu'],
                ['jenis' => 'Meninggalkan sekolah tanpa izin', 'poin' => 20, 'tindak_lanjut' => 'Panggilan orang tua'],
                ['jenis' => 'Berkelahi dengan teman', 'poin' => 25, 'tindak_lanjut' => 'Mediasi dan surat pernyataan'],
                ['jenis' => 'Merokok di lingkungan sekolah', 'poin' => 30, 'tindak_lanjut' => 'Panggilan orang tua dan konseling'],
            ],
            'berat' => [
                ['jenis' => 'Membawa senjata tajam', 'poin' => 50, 'tindak_lanjut' => 'Skorsing 1 minggu dan panggilan orang tua'],
                ['jenis' => 'Bullying terhadap siswa lain', 'poin' => 50, 'tindak_lanjut' => 'Skorsing dan konseling wajib'],
                ['jenis' => 'Mencuri', 'poin' => 75, 'tindak_lanjut' => 'Skorsing dan penggantian kerugian'],
                ['jenis' => 'Merusak fasilitas sekolah', 'poin' => 50, 'tindak_lanjut' => 'Ganti rugi dan skorsing'],
            ],
        ];

        $statusList = ['proses', 'selesai', 'batal'];
        $counter = 0;
        $today = Carbon::now();

        foreach ($siswas as $siswa) {
            // Sebagian besar siswa hanya punya pelanggaran ringan
            // Beberapa siswa punya pelanggaran sedang
            // Sedikit siswa punya pelanggaran berat
            $chance = rand(1, 100);

            if ($chance <= 60) {
                // 60% - pelanggaran ringan saja (1-3 pelanggaran)
                $jumlahPelanggaran = rand(1, 3);
                $kategoriPool = ['ringan'];
            } elseif ($chance <= 90) {
                // 30% - pelanggaran ringan + sedang (1-2 pelanggaran)
                $jumlahPelanggaran = rand(1, 2);
                $kategoriPool = ['ringan', 'sedang'];
            } else {
                // 10% - bisa punya pelanggaran berat (1 pelanggaran)
                $jumlahPelanggaran = 1;
                $kategoriPool = ['sedang', 'berat'];
            }

            for ($i = 0; $i < $jumlahPelanggaran; $i++) {
                $kategori = $kategoriPool[array_rand($kategoriPool)];
                $pelanggaran = $pelanggaranData[$kategori][array_rand($pelanggaranData[$kategori])];

                $tanggal = $today->copy()->subDays(rand(1, 90));

                // Skip hari Sabtu dan Minggu
                if ($tanggal->isWeekend()) {
                    $tanggal = $tanggal->subDays(2);
                }

                // Status berdasarkan tanggal
                if ($tanggal->lt($today->copy()->subDays(14))) {
                    $status = 'selesai';
                } elseif ($tanggal->lt($today->copy()->subDays(3))) {
                    $status = rand(1, 10) <= 7 ? 'selesai' : 'proses';
                } else {
                    $status = $statusList[array_rand($statusList)];
                }

                Pelanggaran::create([
                    'siswa_id' => $siswa->id,
                    'semester_id' => $semester->id,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'jenis_pelanggaran' => $pelanggaran['jenis'],
                    'kategori' => $kategori,
                    'poin' => $pelanggaran['poin'],
                    'deskripsi' => 'Siswa kedapatan '.strtolower($pelanggaran['jenis']),
                    'bukti' => null,
                    'pelapor_id' => $pelapor?->id,
                    'status' => $status,
                    'tindak_lanjut' => $status !== 'proses' ? $pelanggaran['tindak_lanjut'] : null,
                    'catatan' => $status === 'selesai' ? 'Siswa sudah menunjukkan perbaikan perilaku' : null,
                ]);

                $counter++;
            }
        }

        $this->command->info("Pelanggaran seeded successfully: {$counter} records");
    }
}
