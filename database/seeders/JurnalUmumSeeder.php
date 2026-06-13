<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Hanya men-seed jurnal MANUAL yang memang dibuat manual di produksi:
 * beban operasional rutin (listrik, air, internet, ATK, kebersihan) dan
 * pendapatan lain-lain. Pasangan D/K dibuat dengan nomor bukti & token unik.
 *
 * SENGAJA TIDAK men-seed jurnal SPP maupun gaji (temuan audit #3): di produksi
 * jurnal SPP terbentuk via PembayaranJournalPoster (saat Pembayaran berhasil)
 * dan jurnal gaji via SlipGaji::approve()/bayar() — keduanya idempoten & tunduk
 * cut-off. Memalsukannya di seeder membuat demo terlihat benar padahal produksi
 * tak akan pernah menyamai. Untuk demo jurnal SPP/gaji/tabungan yang JUJUR,
 * lihat DemoKeuanganPascaCutoffSeeder yang menjalankan poster nyata.
 *
 * Tanggal jurnal manual ini berada pada jendela pasca cut-off (mulai bulan
 * cut-off s/d bulan berjalan) agar konsisten dengan era pembukuan otomatis dan
 * tampil di Buku Besar/Laba Rugi bersama jurnal poster.
 */
class JurnalUmumSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();

        $akuns = Akun::all()->keyBy('kode');

        if ($akuns->isEmpty()) {
            $this->command->warn('Tidak ada akun. Silakan jalankan AkunSeeder terlebih dahulu.');

            return;
        }

        $cutoff = Carbon::parse(config('akuntansi.cutoff_posting'))->startOfMonth();
        $bulanBerjalan = Carbon::now()->startOfMonth();

        // Bila bulan berjalan masih sebelum cut-off (mis. demo dijalankan sebelum
        // TA baru), tetap buat satu bulan jurnal manual tepat di bulan cut-off.
        $mulai = $bulanBerjalan->lessThan($cutoff) ? $cutoff->copy() : $cutoff->copy();
        $akhir = $bulanBerjalan->greaterThan($cutoff) ? $bulanBerjalan->copy() : $cutoff->copy();

        $globalCounter = 0;
        $cursor = $mulai->copy();

        while ($cursor->lessThanOrEqualTo($akhir)) {
            $globalCounter += $this->seedBulan($cursor->copy(), $akuns, $admin);
            $cursor->addMonthNoOverflow();
        }

        $this->command->info("JurnalUmum (manual operasional) seeded: {$globalCounter} transaksi.");
    }

    /**
     * Seed beban operasional + pendapatan lain untuk satu bulan. Mengembalikan
     * jumlah transaksi (pasangan jurnal) yang dibuat.
     *
     * @param  Collection<string, Akun>  $akuns
     */
    private function seedBulan(Carbon $bulan, $akuns, ?User $admin): int
    {
        $awalBulan = $bulan->copy()->startOfMonth();
        $count = 0;

        /** @var list<array{kode_beban: string, keterangan: string, nominal: int, offsetHari: int, prefix: string}> $bebanList */
        $bebanList = [
            [
                'kode_beban' => '5-2001',
                'keterangan' => 'Pembayaran tagihan listrik bulan '.$bulan->translatedFormat('F Y'),
                'nominal' => rand(2, 5) * 1000000,
                'offsetHari' => rand(5, 15),
                'prefix' => 'JU-OPS-'.$bulan->format('Ym').'-001',
            ],
            [
                'kode_beban' => '5-2002',
                'keterangan' => 'Pembayaran tagihan air bulan '.$bulan->translatedFormat('F Y'),
                'nominal' => rand(500, 1500) * 1000,
                'offsetHari' => rand(5, 15),
                'prefix' => 'JU-OPS-'.$bulan->format('Ym').'-002',
            ],
            [
                'kode_beban' => '5-2003',
                'keterangan' => 'Pembayaran tagihan internet bulan '.$bulan->translatedFormat('F Y'),
                'nominal' => rand(500, 2000) * 1000,
                'offsetHari' => rand(1, 10),
                'prefix' => 'JU-OPS-'.$bulan->format('Ym').'-003',
            ],
            [
                'kode_beban' => '5-3002',
                'keterangan' => 'Pembelian perlengkapan kebersihan',
                'nominal' => rand(200, 500) * 1000,
                'offsetHari' => rand(1, 20),
                'prefix' => 'JU-OPS-'.$bulan->format('Ym').'-005',
            ],
        ];

        if (rand(1, 10) <= 6) {
            $bebanList[] = [
                'kode_beban' => '5-3001',
                'keterangan' => 'Pembelian alat tulis kantor',
                'nominal' => rand(300, 800) * 1000,
                'offsetHari' => rand(1, 25),
                'prefix' => 'JU-OPS-'.$bulan->format('Ym').'-004',
            ];
        }

        foreach ($bebanList as $beban) {
            $akunBeban = $akuns[$beban['kode_beban']] ?? null;
            $akunKas = $akuns['1-1001'] ?? null;

            if ($akunBeban === null || $akunKas === null) {
                continue;
            }

            $tanggal = $awalBulan->copy()->addDays($beban['offsetHari']);
            $this->postManual($beban['prefix'], $tanggal, $beban['keterangan'], $akunBeban, $akunKas, (string) $beban['nominal'], $admin);
            $count++;
        }

        // Pendapatan lain-lain (sekali-sekali).
        if (rand(1, 10) <= 4) {
            $akunPendapatanLain = $akuns['4-1005'] ?? null;
            $akunKas = $akuns['1-1001'] ?? null;

            if ($akunPendapatanLain !== null && $akunKas !== null) {
                $keterangan = collect([
                    'Pendapatan dari kegiatan ekstrakurikuler',
                    'Pendapatan dari penyewaan aula',
                    'Pendapatan dari donasi',
                    'Pendapatan dari kantin sekolah',
                ])->random();

                $tanggal = $awalBulan->copy()->addDays(rand(1, 25));
                $this->postManual(
                    'JU-LAIN-'.$bulan->format('Ym').'-001',
                    $tanggal,
                    $keterangan,
                    $akunKas,
                    $akunPendapatanLain,
                    (string) (rand(500, 2000) * 1000),
                    $admin,
                );
                $count++;
            }
        }

        return $count;
    }

    /**
     * Buat satu pasangan jurnal manual seimbang (debit $akunDebit / kredit
     * $akunKredit) dengan nomor bukti & token unik agar tidak bentrok.
     */
    private function postManual(string $prefix, Carbon $tanggal, string $keterangan, Akun $akunDebit, Akun $akunKredit, string $nominal, ?User $admin): void
    {
        $token = ((int) JurnalUmum::query()->withTrashed()->max('id')) + 1;

        JurnalUmum::create([
            'nomor_bukti' => $prefix.'-D-'.$token,
            'tanggal' => $tanggal->format('Y-m-d'),
            'keterangan' => $keterangan,
            'akun_id' => $akunDebit->id,
            'debit' => $nominal,
            'kredit' => 0,
            'jenis_referensi' => null,
            'created_by' => $admin?->id,
        ]);

        JurnalUmum::create([
            'nomor_bukti' => $prefix.'-K-'.$token,
            'tanggal' => $tanggal->format('Y-m-d'),
            'keterangan' => $keterangan,
            'akun_id' => $akunKredit->id,
            'debit' => 0,
            'kredit' => $nominal,
            'jenis_referensi' => null,
            'created_by' => $admin?->id,
        ]);
    }
}
