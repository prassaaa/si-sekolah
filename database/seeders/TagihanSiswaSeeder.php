<?php

namespace Database\Seeders;

use App\Models\JenisPembayaran;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TagihanSiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $semesterAktif = Semester::where('is_active', true)->first();

        if (! $semesterAktif) {
            $this->command->warn('Tidak ada semester aktif. Silakan jalankan SemesterSeeder terlebih dahulu.');

            return;
        }

        // Ambil siswa dari semua kelas untuk distribusi merata
        $siswas = Siswa::where('is_active', true)
            ->whereNull('deleted_at')
            ->whereNotNull('kelas_id')
            ->get();

        if ($siswas->isEmpty()) {
            $this->command->warn('Tidak ada siswa aktif. Silakan jalankan SiswaSeeder terlebih dahulu.');

            return;
        }

        // Ambil jenis pembayaran SPP (bulanan)
        $sppPembayarans = JenisPembayaran::where('is_active', true)
            ->where('jenis', 'bulanan')
            ->whereIn('kode', ['SPP-7', 'SPP-8', 'SPP-9'])
            ->get();

        // Ambil jenis pembayaran lainnya untuk variasi
        $otherPembayarans = JenisPembayaran::where('is_active', true)
            ->whereNotIn('jenis', ['bulanan'])
            ->take(5)
            ->get();

        $counter = 0;
        $today = Carbon::now();

        foreach ($siswas as $siswa) {
            // Buat tagihan SPP untuk beberapa bulan terakhir
            $spp = $sppPembayarans->random();

            // Generate tagihan untuk 6 bulan terakhir
            for ($i = 5; $i >= 0; $i--) {
                $bulan = $today->copy()->subMonths($i);
                $nomorTagihan = 'TGH-'.$bulan->format('Ym').'-'.str_pad($siswa->id, 4, '0', STR_PAD_LEFT).'-'.str_pad($counter + 1, 3, '0', STR_PAD_LEFT);

                // Cek apakah tagihan sudah ada
                $exists = TagihanSiswa::where('nomor_tagihan', $nomorTagihan)->exists();
                if ($exists) {
                    $counter++;

                    continue;
                }

                $nominal = $spp->nominal;
                $diskon = rand(0, 10) > 7 ? $nominal * 0.1 : 0; // 30% chance untuk diskon 10%
                $totalTagihan = $nominal - $diskon;

                // Tentukan status dan pembayaran berdasarkan bulan
                if ($i >= 3) {
                    // Bulan lama - sudah lunas
                    $status = 'lunas';
                    $totalTerbayar = $totalTagihan;
                    $sisaTagihan = 0;
                } elseif ($i >= 1) {
                    // Bulan kemarin - sebagian atau lunas
                    $randomStatus = rand(1, 10);
                    if ($randomStatus <= 5) {
                        $status = 'lunas';
                        $totalTerbayar = $totalTagihan;
                        $sisaTagihan = 0;
                    } elseif ($randomStatus <= 8) {
                        $status = 'sebagian';
                        $totalTerbayar = $totalTagihan * (rand(3, 7) / 10);
                        $sisaTagihan = $totalTagihan - $totalTerbayar;
                    } else {
                        $status = 'belum_bayar';
                        $totalTerbayar = 0;
                        $sisaTagihan = $totalTagihan;
                    }
                } else {
                    // Bulan ini - belum bayar atau sebagian
                    $randomStatus = rand(1, 10);
                    if ($randomStatus <= 3) {
                        $status = 'lunas';
                        $totalTerbayar = $totalTagihan;
                        $sisaTagihan = 0;
                    } elseif ($randomStatus <= 5) {
                        $status = 'sebagian';
                        $totalTerbayar = $totalTagihan * (rand(3, 7) / 10);
                        $sisaTagihan = $totalTagihan - $totalTerbayar;
                    } else {
                        $status = 'belum_bayar';
                        $totalTerbayar = 0;
                        $sisaTagihan = $totalTagihan;
                    }
                }

                TagihanSiswa::create([
                    'siswa_id' => $siswa->id,
                    'jenis_pembayaran_id' => $spp->id,
                    'semester_id' => $semesterAktif->id,
                    'nomor_tagihan' => $nomorTagihan,
                    'nominal' => $nominal,
                    'diskon' => $diskon,
                    'total_tagihan' => $totalTagihan,
                    'total_terbayar' => $totalTerbayar,
                    'sisa_tagihan' => $sisaTagihan,
                    'tanggal_tagihan' => $bulan->startOfMonth()->format('Y-m-d'),
                    'tanggal_jatuh_tempo' => $bulan->copy()->day(10)->format('Y-m-d'),
                    'status' => $status,
                    'keterangan' => 'Tagihan SPP bulan '.$bulan->translatedFormat('F Y'),
                ]);

                $counter++;
            }

            // Buat beberapa tagihan non-SPP (buku, seragam, dll)
            if ($otherPembayarans->isNotEmpty() && rand(1, 10) <= 4) {
                $other = $otherPembayarans->random();
                $nomorTagihan = 'TGH-OTH-'.$today->format('Ymd').'-'.str_pad($siswa->id, 4, '0', STR_PAD_LEFT).'-'.str_pad($counter + 1, 3, '0', STR_PAD_LEFT);

                $exists = TagihanSiswa::where('nomor_tagihan', $nomorTagihan)->exists();
                if (! $exists) {
                    $nominal = $other->nominal;
                    $diskon = 0;
                    $totalTagihan = $nominal;

                    $randomStatus = rand(1, 10);
                    if ($randomStatus <= 4) {
                        $status = 'lunas';
                        $totalTerbayar = $totalTagihan;
                        $sisaTagihan = 0;
                    } elseif ($randomStatus <= 6) {
                        $status = 'sebagian';
                        $totalTerbayar = $totalTagihan * 0.5;
                        $sisaTagihan = $totalTagihan - $totalTerbayar;
                    } else {
                        $status = 'belum_bayar';
                        $totalTerbayar = 0;
                        $sisaTagihan = $totalTagihan;
                    }

                    TagihanSiswa::create([
                        'siswa_id' => $siswa->id,
                        'jenis_pembayaran_id' => $other->id,
                        'semester_id' => $semesterAktif->id,
                        'nomor_tagihan' => $nomorTagihan,
                        'nominal' => $nominal,
                        'diskon' => $diskon,
                        'total_tagihan' => $totalTagihan,
                        'total_terbayar' => $totalTerbayar,
                        'sisa_tagihan' => $sisaTagihan,
                        'tanggal_tagihan' => $today->format('Y-m-d'),
                        'tanggal_jatuh_tempo' => $today->copy()->addDays(30)->format('Y-m-d'),
                        'status' => $status,
                        'keterangan' => 'Tagihan '.$other->nama,
                    ]);

                    $counter++;
                }
            }
        }

        $this->command->info("TagihanSiswa seeded successfully: {$counter} records");
    }
}
