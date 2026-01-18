<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class JurnalUmumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::first();
        $today = Carbon::now();

        // Ambil akun berdasarkan kode
        $akuns = Akun::all()->keyBy('kode');

        if ($akuns->isEmpty()) {
            $this->command->warn('Tidak ada akun. Silakan jalankan AkunSeeder terlebih dahulu.');

            return;
        }

        $globalCounter = 0;

        // Generate jurnal untuk 6 bulan terakhir
        for ($monthOffset = 5; $monthOffset >= 0; $monthOffset--) {
            $bulan = $today->copy()->subMonths($monthOffset);
            $tanggalAwal = $bulan->copy()->startOfMonth();
            $monthCounter = 0;

            // === PENDAPATAN SPP ===
            // Generate beberapa transaksi penerimaan SPP per bulan
            $jumlahTransaksiSpp = rand(15, 25);
            for ($i = 0; $i < $jumlahTransaksiSpp; $i++) {
                $tanggal = $tanggalAwal->copy()->addDays(rand(0, 27));
                if ($tanggal->isWeekend()) {
                    $tanggal = $tanggal->addDays(2);
                }

                $nominal = rand(3, 8) * 100000; // 300.000 - 800.000
                $monthCounter++;
                $baseNomorBukti = 'JU-SPP-'.$bulan->format('Ym').'-'.str_pad($monthCounter, 4, '0', STR_PAD_LEFT);
                $metode = collect(['Kas', 'Bank BCA', 'Bank Mandiri', 'Bank BSI'])->random();

                // Debit: Kas/Bank
                $akunDebit = $metode === 'Kas' ? '1-1001' : ($metode === 'Bank BCA' ? '1-1002' : ($metode === 'Bank Mandiri' ? '1-1003' : '1-1004'));

                JurnalUmum::create([
                    'nomor_bukti' => $baseNomorBukti.'-D',
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'keterangan' => 'Penerimaan pembayaran SPP siswa via '.$metode,
                    'akun_id' => $akuns[$akunDebit]->id,
                    'debit' => $nominal,
                    'kredit' => 0,
                    'referensi' => 'SPP-'.$bulan->format('Ym'),
                    'jenis_referensi' => 'pembayaran',
                    'created_by' => $admin?->id,
                ]);

                // Kredit: Pendapatan SPP
                JurnalUmum::create([
                    'nomor_bukti' => $baseNomorBukti.'-K',
                    'tanggal' => $tanggal->format('Y-m-d'),
                    'keterangan' => 'Penerimaan pembayaran SPP siswa via '.$metode,
                    'akun_id' => $akuns['4-1001']->id,
                    'debit' => 0,
                    'kredit' => $nominal,
                    'referensi' => 'SPP-'.$bulan->format('Ym'),
                    'jenis_referensi' => 'pembayaran',
                    'created_by' => $admin?->id,
                ]);

                $globalCounter++;
            }

            // === BEBAN GAJI ===
            $tanggalGaji = $tanggalAwal->copy()->endOfMonth();
            if ($tanggalGaji->isWeekend()) {
                $tanggalGaji = $tanggalGaji->subDays(2);
            }

            $gajiGuru = rand(15, 25) * 1000000; // 15-25 juta
            $gajiKaryawan = rand(8, 15) * 1000000; // 8-15 juta

            $baseNomorBuktiGaji = 'JU-GAJI-'.$bulan->format('Ym').'-001';

            // Debit: Beban Gaji Guru
            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiGaji.'-D',
                'tanggal' => $tanggalGaji->format('Y-m-d'),
                'keterangan' => 'Pembayaran gaji guru bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['5-1001']->id,
                'debit' => $gajiGuru,
                'kredit' => 0,
                'referensi' => 'GAJI-'.$bulan->format('Ym'),
                'jenis_referensi' => 'slip_gaji',
                'created_by' => $admin?->id,
            ]);

            // Kredit: Kas (pembayaran gaji guru)
            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiGaji.'-K',
                'tanggal' => $tanggalGaji->format('Y-m-d'),
                'keterangan' => 'Pembayaran gaji guru bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['1-1001']->id,
                'debit' => 0,
                'kredit' => $gajiGuru,
                'referensi' => 'GAJI-'.$bulan->format('Ym'),
                'jenis_referensi' => 'slip_gaji',
                'created_by' => $admin?->id,
            ]);

            $baseNomorBuktiGaji2 = 'JU-GAJI-'.$bulan->format('Ym').'-002';

            // Debit: Beban Gaji Karyawan
            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiGaji2.'-D',
                'tanggal' => $tanggalGaji->format('Y-m-d'),
                'keterangan' => 'Pembayaran gaji karyawan bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['5-1002']->id,
                'debit' => $gajiKaryawan,
                'kredit' => 0,
                'referensi' => 'GAJI-'.$bulan->format('Ym'),
                'jenis_referensi' => 'slip_gaji',
                'created_by' => $admin?->id,
            ]);

            // Kredit: Kas (pembayaran gaji karyawan)
            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiGaji2.'-K',
                'tanggal' => $tanggalGaji->format('Y-m-d'),
                'keterangan' => 'Pembayaran gaji karyawan bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['1-1001']->id,
                'debit' => 0,
                'kredit' => $gajiKaryawan,
                'referensi' => 'GAJI-'.$bulan->format('Ym'),
                'jenis_referensi' => 'slip_gaji',
                'created_by' => $admin?->id,
            ]);

            $globalCounter += 2;

            // === BEBAN OPERASIONAL BULANAN ===

            // Beban Listrik
            $bebanListrik = rand(2, 5) * 1000000;
            $tanggalListrik = $tanggalAwal->copy()->addDays(rand(5, 15));
            $baseNomorBuktiListrik = 'JU-OPS-'.$bulan->format('Ym').'-001';

            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiListrik.'-D',
                'tanggal' => $tanggalListrik->format('Y-m-d'),
                'keterangan' => 'Pembayaran tagihan listrik bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['5-2001']->id,
                'debit' => $bebanListrik,
                'kredit' => 0,
                'jenis_referensi' => 'operasional',
                'created_by' => $admin?->id,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiListrik.'-K',
                'tanggal' => $tanggalListrik->format('Y-m-d'),
                'keterangan' => 'Pembayaran tagihan listrik bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['1-1001']->id,
                'debit' => 0,
                'kredit' => $bebanListrik,
                'jenis_referensi' => 'operasional',
                'created_by' => $admin?->id,
            ]);

            $globalCounter++;

            // Beban Air
            $bebanAir = rand(500, 1500) * 1000;
            $tanggalAir = $tanggalAwal->copy()->addDays(rand(5, 15));
            $baseNomorBuktiAir = 'JU-OPS-'.$bulan->format('Ym').'-002';

            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiAir.'-D',
                'tanggal' => $tanggalAir->format('Y-m-d'),
                'keterangan' => 'Pembayaran tagihan air bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['5-2002']->id,
                'debit' => $bebanAir,
                'kredit' => 0,
                'jenis_referensi' => 'operasional',
                'created_by' => $admin?->id,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiAir.'-K',
                'tanggal' => $tanggalAir->format('Y-m-d'),
                'keterangan' => 'Pembayaran tagihan air bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['1-1001']->id,
                'debit' => 0,
                'kredit' => $bebanAir,
                'jenis_referensi' => 'operasional',
                'created_by' => $admin?->id,
            ]);

            $globalCounter++;

            // Beban Internet
            $bebanInternet = rand(500, 2000) * 1000;
            $tanggalInternet = $tanggalAwal->copy()->addDays(rand(1, 10));
            $baseNomorBuktiInternet = 'JU-OPS-'.$bulan->format('Ym').'-003';

            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiInternet.'-D',
                'tanggal' => $tanggalInternet->format('Y-m-d'),
                'keterangan' => 'Pembayaran tagihan internet bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['5-2003']->id,
                'debit' => $bebanInternet,
                'kredit' => 0,
                'jenis_referensi' => 'operasional',
                'created_by' => $admin?->id,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiInternet.'-K',
                'tanggal' => $tanggalInternet->format('Y-m-d'),
                'keterangan' => 'Pembayaran tagihan internet bulan '.$bulan->translatedFormat('F Y'),
                'akun_id' => $akuns['1-1001']->id,
                'debit' => 0,
                'kredit' => $bebanInternet,
                'jenis_referensi' => 'operasional',
                'created_by' => $admin?->id,
            ]);

            $globalCounter++;

            // Beban ATK (tidak setiap bulan)
            if (rand(1, 10) <= 6) {
                $bebanAtk = rand(300, 800) * 1000;
                $tanggalAtk = $tanggalAwal->copy()->addDays(rand(1, 25));
                $baseNomorBuktiAtk = 'JU-OPS-'.$bulan->format('Ym').'-004';

                JurnalUmum::create([
                    'nomor_bukti' => $baseNomorBuktiAtk.'-D',
                    'tanggal' => $tanggalAtk->format('Y-m-d'),
                    'keterangan' => 'Pembelian alat tulis kantor',
                    'akun_id' => $akuns['5-3001']->id,
                    'debit' => $bebanAtk,
                    'kredit' => 0,
                    'jenis_referensi' => 'operasional',
                    'created_by' => $admin?->id,
                ]);

                JurnalUmum::create([
                    'nomor_bukti' => $baseNomorBuktiAtk.'-K',
                    'tanggal' => $tanggalAtk->format('Y-m-d'),
                    'keterangan' => 'Pembelian alat tulis kantor',
                    'akun_id' => $akuns['1-1001']->id,
                    'debit' => 0,
                    'kredit' => $bebanAtk,
                    'jenis_referensi' => 'operasional',
                    'created_by' => $admin?->id,
                ]);

                $globalCounter++;
            }

            // Beban Kebersihan
            $bebanKebersihan = rand(200, 500) * 1000;
            $tanggalKebersihan = $tanggalAwal->copy()->addDays(rand(1, 20));
            $baseNomorBuktiKebersihan = 'JU-OPS-'.$bulan->format('Ym').'-005';

            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiKebersihan.'-D',
                'tanggal' => $tanggalKebersihan->format('Y-m-d'),
                'keterangan' => 'Pembelian perlengkapan kebersihan',
                'akun_id' => $akuns['5-3002']->id,
                'debit' => $bebanKebersihan,
                'kredit' => 0,
                'jenis_referensi' => 'operasional',
                'created_by' => $admin?->id,
            ]);

            JurnalUmum::create([
                'nomor_bukti' => $baseNomorBuktiKebersihan.'-K',
                'tanggal' => $tanggalKebersihan->format('Y-m-d'),
                'keterangan' => 'Pembelian perlengkapan kebersihan',
                'akun_id' => $akuns['1-1001']->id,
                'debit' => 0,
                'kredit' => $bebanKebersihan,
                'jenis_referensi' => 'operasional',
                'created_by' => $admin?->id,
            ]);

            $globalCounter++;

            // === PENDAPATAN LAINNYA (sekali-sekali) ===
            if (rand(1, 10) <= 4) {
                $pendapatanLain = rand(500, 2000) * 1000;
                $tanggalLain = $tanggalAwal->copy()->addDays(rand(1, 25));
                $baseNomorBuktiLain = 'JU-LAIN-'.$bulan->format('Ym').'-001';

                $keteranganLain = collect([
                    'Pendapatan dari kegiatan ekstrakurikuler',
                    'Pendapatan dari penyewaan aula',
                    'Pendapatan dari donasi',
                    'Pendapatan dari kantin sekolah',
                ])->random();

                JurnalUmum::create([
                    'nomor_bukti' => $baseNomorBuktiLain.'-D',
                    'tanggal' => $tanggalLain->format('Y-m-d'),
                    'keterangan' => $keteranganLain,
                    'akun_id' => $akuns['1-1001']->id,
                    'debit' => $pendapatanLain,
                    'kredit' => 0,
                    'jenis_referensi' => 'lainnya',
                    'created_by' => $admin?->id,
                ]);

                JurnalUmum::create([
                    'nomor_bukti' => $baseNomorBuktiLain.'-K',
                    'tanggal' => $tanggalLain->format('Y-m-d'),
                    'keterangan' => $keteranganLain,
                    'akun_id' => $akuns['4-1005']->id,
                    'debit' => 0,
                    'kredit' => $pendapatanLain,
                    'jenis_referensi' => 'lainnya',
                    'created_by' => $admin?->id,
                ]);

                $globalCounter++;
            }
        }

        $this->command->info("JurnalUmum seeded successfully: {$globalCounter} transaksi (jurnal entries)");
    }
}
