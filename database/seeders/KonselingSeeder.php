<?php

namespace Database\Seeders;

use App\Models\Konseling;
use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class KonselingSeeder extends Seeder
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
            ->take(20)
            ->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Tidak ada siswa aktif. Silakan jalankan SiswaSeeder terlebih dahulu.');

            return;
        }

        $konselor = Pegawai::first();

        // Definisi konseling berdasarkan kategori
        $konselingData = [
            'akademik' => [
                ['permasalahan' => 'Kesulitan memahami pelajaran Matematika', 'hasil' => 'Siswa akan mengikuti bimbingan tambahan', 'rekomendasi' => 'Mengikuti les tambahan dan diskusi kelompok'],
                ['permasalahan' => 'Nilai menurun drastis', 'hasil' => 'Ditemukan masalah konsentrasi belajar', 'rekomendasi' => 'Evaluasi jadwal belajar dan kurangi penggunaan gadget'],
                ['permasalahan' => 'Kesulitan memahami Bahasa Inggris', 'hasil' => 'Siswa butuh pendekatan belajar yang berbeda', 'rekomendasi' => 'Metode belajar visual dan audio'],
                ['permasalahan' => 'Sulit fokus di kelas', 'hasil' => 'Siswa membutuhkan tempat duduk di depan', 'rekomendasi' => 'Pindah posisi duduk dan observasi lanjutan'],
            ],
            'pribadi' => [
                ['permasalahan' => 'Sering merasa cemas dan khawatir', 'hasil' => 'Siswa belajar teknik relaksasi', 'rekomendasi' => 'Latihan pernapasan dan mindfulness'],
                ['permasalahan' => 'Kurang percaya diri', 'hasil' => 'Siswa termotivasi untuk lebih berani', 'rekomendasi' => 'Ikut kegiatan ekstrakurikuler untuk meningkatkan kepercayaan diri'],
                ['permasalahan' => 'Masalah emosional', 'hasil' => 'Siswa dapat mengekspresikan perasaan', 'rekomendasi' => 'Konseling lanjutan dan komunikasi dengan orang tua'],
                ['permasalahan' => 'Masalah manajemen waktu', 'hasil' => 'Siswa membuat jadwal harian', 'rekomendasi' => 'Gunakan planner dan evaluasi mingguan'],
                ['permasalahan' => 'Masalah hubungan dengan orang tua', 'hasil' => 'Siswa belajar cara komunikasi efektif', 'rekomendasi' => 'Pertemuan dengan orang tua dan terapi keluarga'],
                ['permasalahan' => 'Orang tua bercerai', 'hasil' => 'Siswa dapat mengekspresikan perasaan', 'rekomendasi' => 'Konseling berkelanjutan dan dukungan emosional'],
            ],
            'sosial' => [
                ['permasalahan' => 'Kesulitan bergaul dengan teman', 'hasil' => 'Siswa belajar keterampilan sosial', 'rekomendasi' => 'Ikut kegiatan kelompok dan latihan komunikasi'],
                ['permasalahan' => 'Konflik dengan teman sekelas', 'hasil' => 'Mediasi dilakukan dan kedua pihak berdamai', 'rekomendasi' => 'Monitoring hubungan dan konseling lanjutan jika perlu'],
                ['permasalahan' => 'Merasa dikucilkan', 'hasil' => 'Guru kelas akan membantu integrasi siswa', 'rekomendasi' => 'Pemberian tugas kelompok dengan siswa lain'],
            ],
            'karir' => [
                ['permasalahan' => 'Bingung memilih jurusan setelah lulus', 'hasil' => 'Siswa mendapat informasi berbagai pilihan karir', 'rekomendasi' => 'Tes minat bakat dan kunjungan ke perguruan tinggi'],
                ['permasalahan' => 'Tidak yakin dengan cita-cita', 'hasil' => 'Siswa mulai mengenali potensi diri', 'rekomendasi' => 'Eksplorasi berbagai bidang melalui magang atau workshop'],
            ],
            'lainnya' => [
                ['permasalahan' => 'Membutuhkan bimbingan umum', 'hasil' => 'Siswa mendapat arahan', 'rekomendasi' => 'Evaluasi berkala'],
            ],
        ];

        $jenisList = ['individu', 'kelompok'];
        $statusList = ['dijadwalkan', 'berlangsung', 'selesai', 'batal'];

        $counter = 0;
        $today = Carbon::now();

        foreach ($siswas as $siswa) {
            $jumlahKonseling = rand(1, 3);

            for ($i = 0; $i < $jumlahKonseling; $i++) {
                $kategori = array_rand($konselingData);
                $konseling = $konselingData[$kategori][array_rand($konselingData[$kategori])];

                $tanggal = $today->copy()->subDays(rand(1, 90));

                // Skip hari Sabtu dan Minggu
                if ($tanggal->isWeekend()) {
                    $tanggal = $tanggal->subDays(2);
                }

                // Status berdasarkan tanggal
                if ($tanggal->lt($today->copy()->subDays(7))) {
                    $status = rand(1, 10) <= 9 ? 'selesai' : 'batal';
                } elseif ($tanggal->lt($today)) {
                    $statusChance = rand(1, 10);
                    if ($statusChance <= 7) {
                        $status = 'selesai';
                    } elseif ($statusChance <= 8) {
                        $status = 'berlangsung';
                    } else {
                        $status = 'batal';
                    }
                } else {
                    $status = 'dijadwalkan';
                }

                $waktuMulai = sprintf('%02d:%02d', rand(8, 14), rand(0, 59));
                $waktuSelesai = sprintf('%02d:%02d', rand(15, 16), rand(0, 59));

                $perluTindakLanjut = rand(1, 10) <= 4; // 40% perlu tindak lanjut

                Konseling::create([
                    'siswa_id' => $siswa->id,
                    'semester_id' => $semester->id,
                    'konselor_id' => $konselor?->id,
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'waktu_mulai' => $waktuMulai,
                    'waktu_selesai' => $status === 'selesai' ? $waktuSelesai : null,
                    'jenis' => $jenisList[array_rand($jenisList)],
                    'kategori' => $kategori,
                    'permasalahan' => $konseling['permasalahan'],
                    'hasil_konseling' => $status === 'selesai' ? $konseling['hasil'] : null,
                    'rekomendasi' => $status === 'selesai' ? $konseling['rekomendasi'] : null,
                    'status' => $status,
                    'perlu_tindak_lanjut' => $status === 'selesai' && $perluTindakLanjut,
                    'tanggal_tindak_lanjut' => $status === 'selesai' && $perluTindakLanjut ? $tanggal->copy()->addDays(14)->format('Y-m-d') : null,
                    'catatan' => $status === 'batal' ? 'Siswa tidak hadir' : null,
                ]);

                $counter++;
            }
        }

        $this->command->info("Konseling seeded successfully: {$counter} records");
    }
}
