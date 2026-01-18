<?php

namespace Database\Seeders;

use App\Models\Kelulusan;
use App\Models\Pegawai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

class KelulusanSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil tahun ajaran yang sudah tidak aktif (tahun lalu)
        $tahunAjaranLalu = TahunAjaran::where('is_active', false)
            ->orderBy('tanggal_mulai', 'desc')
            ->first();

        if (! $tahunAjaranLalu) {
            $this->command->warn('Tidak ada tahun ajaran sebelumnya untuk kelulusan.');

            return;
        }

        // Ambil kepala sekolah sebagai penyetuju
        $penyetuju = Pegawai::whereHas('jabatan', function ($query) {
            $query->where('nama', 'like', '%Kepala Sekolah%');
        })->first();

        if (! $penyetuju) {
            $penyetuju = Pegawai::first();
        }

        // Ambil beberapa siswa untuk dijadikan contoh kelulusan
        $siswaList = Siswa::inRandomOrder()->limit(20)->get();

        foreach ($siswaList as $index => $siswa) {
            // Tentukan status berdasarkan probabilitas
            $rand = rand(1, 100);
            if ($rand <= 90) {
                $status = 'lulus';
                $nilaiAkhir = rand(75, 95) + (rand(0, 99) / 100);
            } elseif ($rand <= 98) {
                $status = 'pending';
                $nilaiAkhir = rand(70, 75) + (rand(0, 99) / 100);
            } else {
                $status = 'tidak_lulus';
                $nilaiAkhir = rand(50, 69) + (rand(0, 99) / 100);
            }

            // Tentukan predikat berdasarkan nilai akhir
            if ($nilaiAkhir >= 90) {
                $predikat = 'sangat_baik';
            } elseif ($nilaiAkhir >= 80) {
                $predikat = 'baik';
            } elseif ($nilaiAkhir >= 70) {
                $predikat = 'cukup';
            } else {
                $predikat = 'kurang';
            }

            // Generate nomor ijazah dan SKHUN
            $tahun = $tahunAjaranLalu->tanggal_selesai->format('Y');
            $nomorUrut = str_pad($index + 1, 4, '0', STR_PAD_LEFT);

            Kelulusan::firstOrCreate(
                [
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahunAjaranLalu->id,
                ],
                [
                    'nomor_ijazah' => "IJ-{$tahun}-{$nomorUrut}",
                    'nomor_skhun' => "SKHUN-{$tahun}-{$nomorUrut}",
                    'tanggal_lulus' => now()->subMonths(rand(2, 6)),
                    'status' => $status,
                    'nilai_akhir' => $nilaiAkhir,
                    'predikat' => $predikat,
                    'tujuan_sekolah' => $status === 'lulus'
                        ? collect([
                            'SMA Negeri 1',
                            'SMA Negeri 2',
                            'SMK Negeri 1',
                            'MAN 1',
                            'SMA Swasta Unggulan',
                        ])->random()
                        : null,
                    'catatan' => $status === 'lulus'
                        ? 'Selamat atas kelulusan, terus semangat di jenjang pendidikan berikutnya'
                        : ($status === 'tidak_lulus'
                            ? 'Silakan mengikuti program perbaikan untuk meningkatkan nilai'
                            : 'Menunggu hasil evaluasi akhir'),
                    'disetujui_oleh' => $penyetuju?->id,
                ]
            );
        }

        $this->command->info('Kelulusan seeded successfully.');
    }
}
