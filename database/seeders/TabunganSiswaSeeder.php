<?php

namespace Database\Seeders;

use App\Models\Siswa;
use App\Models\TabunganSiswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TabunganSiswaSeeder extends Seeder
{
    public function run(): void
    {
        $siswas = Siswa::where('is_active', true)
            ->whereNull('deleted_at')
            ->take(50)
            ->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Tidak ada siswa aktif.');

            return;
        }

        $user = User::first();
        $counter = 0;
        $today = Carbon::now();

        foreach ($siswas as $siswa) {
            // Cek apakah siswa sudah punya tabungan
            if (TabunganSiswa::where('siswa_id', $siswa->id)->exists()) {
                continue;
            }

            $saldo = 0;

            // Setor awal (6 bulan yang lalu)
            $nominalAwal = rand(5, 20) * 10000;
            $saldo += $nominalAwal;

            TabunganSiswa::create([
                'siswa_id' => $siswa->id,
                'jenis' => 'setor',
                'nominal' => $nominalAwal,
                'saldo' => $saldo,
                'tanggal' => $today->copy()->subMonths(6)->format('Y-m-d'),
                'keterangan' => 'Setoran awal pembukaan tabungan',
                'user_id' => $user?->id,
            ]);
            $counter++;

            // Generate transaksi untuk 6 bulan terakhir
            for ($bulan = 5; $bulan >= 0; $bulan--) {
                $tanggalBulan = $today->copy()->subMonths($bulan);

                // Setoran bulanan (1-3 kali per bulan)
                $jumlahSetor = rand(1, 3);
                for ($i = 0; $i < $jumlahSetor; $i++) {
                    $tanggalSetor = $tanggalBulan->copy()->day(rand(1, 28));

                    // Skip jika tanggal di masa depan
                    if ($tanggalSetor->gt($today)) {
                        continue;
                    }

                    $nominalSetor = rand(2, 10) * 10000;
                    $saldo += $nominalSetor;

                    TabunganSiswa::create([
                        'siswa_id' => $siswa->id,
                        'jenis' => 'setor',
                        'nominal' => $nominalSetor,
                        'saldo' => $saldo,
                        'tanggal' => $tanggalSetor->format('Y-m-d'),
                        'keterangan' => 'Setoran rutin bulanan',
                        'user_id' => $user?->id,
                    ]);
                    $counter++;
                }

                // Penarikan (30% chance per bulan, hanya jika saldo cukup)
                if (rand(1, 10) <= 3 && $saldo > 50000) {
                    $maxTarik = min($saldo - 50000, 200000); // Sisakan minimal 50000
                    if ($maxTarik > 0) {
                        $nominalTarik = rand(1, (int) ($maxTarik / 10000)) * 10000;
                        $saldo -= $nominalTarik;

                        $tanggalTarik = $tanggalBulan->copy()->day(rand(15, 28));
                        if ($tanggalTarik->gt($today)) {
                            $tanggalTarik = $today;
                        }

                        $keteranganTarik = [
                            'Penarikan untuk keperluan sekolah',
                            'Penarikan untuk membeli buku',
                            'Penarikan untuk keperluan pribadi',
                            'Penarikan untuk kegiatan ekstrakurikuler',
                        ];

                        TabunganSiswa::create([
                            'siswa_id' => $siswa->id,
                            'jenis' => 'tarik',
                            'nominal' => $nominalTarik,
                            'saldo' => $saldo,
                            'tanggal' => $tanggalTarik->format('Y-m-d'),
                            'keterangan' => $keteranganTarik[array_rand($keteranganTarik)],
                            'user_id' => $user?->id,
                        ]);
                        $counter++;
                    }
                }
            }
        }

        $this->command->info('Tabungan Siswa seeded successfully: '.$counter.' records');
    }
}
