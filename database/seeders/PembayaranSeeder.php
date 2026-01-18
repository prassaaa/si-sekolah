<?php

namespace Database\Seeders;

use App\Models\Pegawai;
use App\Models\Pembayaran;
use App\Models\TagihanSiswa;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class PembayaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ambil tagihan yang sudah lunas atau sebagian
        $tagihans = TagihanSiswa::whereIn('status', ['lunas', 'sebagian'])
            ->where('total_terbayar', '>', 0)
            ->get();

        if ($tagihans->isEmpty()) {
            $this->command->warn('Tidak ada tagihan dengan pembayaran. Silakan jalankan TagihanSiswaSeeder terlebih dahulu.');

            return;
        }

        $pegawai = Pegawai::first();
        $metodePembayaran = ['tunai', 'transfer', 'qris', 'virtual_account'];
        $counter = 0;
        $today = Carbon::now();

        foreach ($tagihans as $tagihan) {
            // Cek apakah sudah ada pembayaran untuk tagihan ini
            $existingPembayaran = Pembayaran::where('tagihan_siswa_id', $tagihan->id)->exists();
            if ($existingPembayaran) {
                continue;
            }

            $totalTerbayar = $tagihan->total_terbayar;

            // Jika lunas, buat 1-2 pembayaran
            // Jika sebagian, buat 1 pembayaran
            if ($tagihan->status === 'lunas') {
                // 50% chance untuk pembayaran penuh sekaligus, 50% cicilan
                if (rand(1, 10) <= 5) {
                    // Pembayaran penuh sekaligus
                    $this->createPembayaran(
                        $tagihan,
                        $totalTerbayar,
                        $metodePembayaran[array_rand($metodePembayaran)],
                        $pegawai?->id,
                        $tagihan->tanggal_tagihan,
                        $counter
                    );
                    $counter++;
                } else {
                    // Pembayaran cicilan (2x)
                    $firstPayment = round($totalTerbayar * 0.5, 2);
                    $secondPayment = $totalTerbayar - $firstPayment;

                    // Pembayaran pertama
                    $this->createPembayaran(
                        $tagihan,
                        $firstPayment,
                        $metodePembayaran[array_rand($metodePembayaran)],
                        $pegawai?->id,
                        $tagihan->tanggal_tagihan,
                        $counter
                    );
                    $counter++;

                    // Pembayaran kedua
                    $tanggalBayar = Carbon::parse($tagihan->tanggal_tagihan)->addDays(rand(5, 15));
                    if ($tanggalBayar->gt($today)) {
                        $tanggalBayar = $today;
                    }

                    $this->createPembayaran(
                        $tagihan,
                        $secondPayment,
                        $metodePembayaran[array_rand($metodePembayaran)],
                        $pegawai?->id,
                        $tanggalBayar,
                        $counter
                    );
                    $counter++;
                }
            } else {
                // Status sebagian - buat 1 pembayaran
                $this->createPembayaran(
                    $tagihan,
                    $totalTerbayar,
                    $metodePembayaran[array_rand($metodePembayaran)],
                    $pegawai?->id,
                    $tagihan->tanggal_tagihan,
                    $counter
                );
                $counter++;
            }
        }

        $this->command->info("Pembayaran seeded successfully: {$counter} records");
    }

    private function createPembayaran(
        TagihanSiswa $tagihan,
        float $jumlahBayar,
        string $metodePembayaran,
        ?int $pegawaiId,
        Carbon|string $tanggalBayar,
        int $counter
    ): void {
        $tanggal = $tanggalBayar instanceof Carbon ? $tanggalBayar : Carbon::parse($tanggalBayar);
        $nomorTransaksi = 'PAY-'.$tanggal->format('Ymd').'-'.str_pad($tagihan->id, 4, '0', STR_PAD_LEFT).'-'.str_pad($counter + 1, 4, '0', STR_PAD_LEFT);

        // Pastikan nomor transaksi unik
        $suffix = 0;
        $originalNomor = $nomorTransaksi;
        while (Pembayaran::where('nomor_transaksi', $nomorTransaksi)->exists()) {
            $suffix++;
            $nomorTransaksi = $originalNomor.'-'.$suffix;
        }

        $referensi = match ($metodePembayaran) {
            'transfer' => 'TRF-'.strtoupper(substr(md5(uniqid()), 0, 10)),
            'qris' => 'QRIS-'.strtoupper(substr(md5(uniqid()), 0, 8)),
            'virtual_account' => 'VA-'.rand(1000000000, 9999999999),
            default => null,
        };

        // Buat pembayaran tanpa trigger booted event (karena total_terbayar sudah dihitung di seeder tagihan)
        Pembayaran::withoutEvents(function () use ($tagihan, $nomorTransaksi, $tanggal, $jumlahBayar, $metodePembayaran, $referensi, $pegawaiId) {
            Pembayaran::create([
                'tagihan_siswa_id' => $tagihan->id,
                'nomor_transaksi' => $nomorTransaksi,
                'tanggal_bayar' => $tanggal->format('Y-m-d'),
                'jumlah_bayar' => $jumlahBayar,
                'metode_pembayaran' => $metodePembayaran,
                'referensi_pembayaran' => $referensi,
                'diterima_oleh' => $pegawaiId,
                'keterangan' => 'Pembayaran via '.ucfirst(str_replace('_', ' ', $metodePembayaran)),
                'status' => 'berhasil',
            ]);
        });
    }
}
