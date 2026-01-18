<?php

namespace Database\Seeders;

use App\Models\KenaikanKelas;
use App\Models\Kelas;
use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\Siswa;
use Illuminate\Database\Seeder;

class KenaikanKelasSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil semester genap (semester 2, 4, 6) yang biasanya untuk kenaikan kelas
        $semesterGenap = Semester::whereIn('semester', [2, 4, 6])
            ->where('is_active', false)
            ->first();

        if (! $semesterGenap) {
            $this->command->warn('Tidak ada semester genap untuk kenaikan kelas.');

            return;
        }

        // Ambil kepala sekolah atau wakil kepala sekolah sebagai penyetuju
        $penyetuju = Pegawai::whereHas('jabatan', function ($query) {
            $query->where('nama', 'like', '%Kepala Sekolah%')
                ->orWhere('nama', 'like', '%Wakil%');
        })->first();

        if (! $penyetuju) {
            $penyetuju = Pegawai::first();
        }

        // Ambil kelas-kelas yang ada
        $kelasList = Kelas::orderBy('tingkat')->get();

        if ($kelasList->count() < 2) {
            $this->command->warn('Tidak cukup kelas untuk kenaikan kelas.');

            return;
        }

        // Ambil beberapa siswa untuk dijadikan contoh kenaikan kelas
        $siswaList = Siswa::inRandomOrder()->limit(30)->get();

        foreach ($siswaList as $index => $siswa) {
            // Tentukan kelas asal (random dari kelas yang ada kecuali kelas tertinggi)
            $kelasAsal = $kelasList->random();

            // Cari kelas tujuan (tingkat berikutnya)
            $kelasTujuan = $kelasList->firstWhere('tingkat', $kelasAsal->tingkat + 1);

            // Jika tidak ada kelas tujuan, skip (siswa kelas tertinggi)
            if (! $kelasTujuan) {
                continue;
            }

            // Tentukan status berdasarkan probabilitas
            $rand = rand(1, 100);
            if ($rand <= 85) {
                $status = 'naik';
                $nilaiRataRata = rand(75, 95);
            } elseif ($rand <= 95) {
                $status = 'tinggal';
                $nilaiRataRata = rand(50, 70);
            } else {
                $status = 'pending';
                $nilaiRataRata = rand(70, 75);
            }

            KenaikanKelas::firstOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'semester_id' => $semesterGenap->id,
                ],
                [
                    'kelas_asal_id' => $kelasAsal->id,
                    'kelas_tujuan_id' => $kelasTujuan->id,
                    'status' => $status,
                    'nilai_rata_rata' => $nilaiRataRata + (rand(0, 99) / 100),
                    'peringkat' => rand(1, 40),
                    'catatan' => $status === 'naik'
                        ? 'Siswa menunjukkan prestasi yang baik dan layak naik kelas'
                        : ($status === 'tinggal'
                            ? 'Siswa perlu meningkatkan prestasi akademiknya'
                            : 'Menunggu evaluasi lebih lanjut'),
                    'tanggal_keputusan' => now()->subDays(rand(1, 30)),
                    'disetujui_oleh' => $penyetuju?->id,
                ]
            );
        }

        $this->command->info('Kenaikan Kelas seeded successfully.');
    }
}
