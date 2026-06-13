<?php

use App\Models\JurnalUmum;
use App\Models\Pembayaran;
use App\Models\SlipGaji;
use App\Models\TabunganSiswa;
use App\Services\Accounting\PembayaranJournalPoster;
use App\Services\Accounting\SlipGajiJournalPoster;
use App\Services\Accounting\TabunganJournalPoster;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoKeuanganPascaCutoffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Membuktikan seeder JUJUR (temuan audit #3):
 *  - DatabaseSeeder berjalan tanpa error di sqlite.
 *  - Tidak ada jurnal SPP/gaji PALSU (penanda lama yang tak pernah dibuat kode
 *    produksi).
 *  - Setiap jurnal sistem berasal dari transaksi nyata (poster), seimbang, dan
 *    bertanggal >= cut-off.
 */
it('runs the full DatabaseSeeder on sqlite without error', function () {
    $this->seed(DatabaseSeeder::class);

    // Smoke: data inti terbentuk.
    expect(JurnalUmum::query()->count())->toBeGreaterThan(0);
})->group('seeder-integrity');

it('does not seed any fake SPP/gaji journals with the legacy markers', function () {
    $this->seed(DatabaseSeeder::class);

    // Penanda PALSU lama dari JurnalUmumSeeder versi sebelumnya:
    //  - jenis_referensi 'pembayaran' TANPA referensi_id (poster nyata selalu
    //    mengisi referensi_id), dan
    //  - jenis_referensi 'slip_gaji' (poster nyata memakai 'slip_gaji_akrual').
    $pembayaranTanpaReferensi = JurnalUmum::query()
        ->where('jenis_referensi', PembayaranJournalPoster::JENIS)
        ->whereNull('referensi_id')
        ->count();

    $gajiPalsu = JurnalUmum::query()
        ->where('jenis_referensi', 'slip_gaji')
        ->count();

    expect($pembayaranTanpaReferensi)->toBe(0)
        ->and($gajiPalsu)->toBe(0);
})->group('seeder-integrity');

it('keeps the whole ledger balanced after seeding', function () {
    $this->seed(DatabaseSeeder::class);

    $totalDebit = (float) JurnalUmum::query()->sum('debit');
    $totalKredit = (float) JurnalUmum::query()->sum('kredit');

    expect(round($totalDebit, 2))->toBe(round($totalKredit, 2));
})->group('seeder-integrity');

it('only posts system journals dated on or after the cut-off', function () {
    $this->seed(DatabaseSeeder::class);

    $cutoff = Carbon::parse(config('akuntansi.cutoff_posting'))->startOfDay();

    // Semua jurnal yang dihasilkan poster otomatis (punya jenis_referensi +
    // referensi_id) harus bertanggal >= cut-off. Jurnal manual (jenis_referensi
    // null) dikecualikan.
    $melanggarCutoff = JurnalUmum::query()
        ->whereNotNull('jenis_referensi')
        ->whereNotNull('referensi_id')
        ->whereDate('tanggal', '<', $cutoff)
        ->count();

    expect($melanggarCutoff)->toBe(0);
})->group('seeder-integrity');

it('forms honest journals from real posters for the post-cut-off demo batch', function () {
    $this->seed(DatabaseSeeder::class);

    // Pembayaran demo pasca cut-off → jurnal pembayaran nyata (D Kas/K Pendapatan).
    $pembayaranDemo = Pembayaran::query()
        ->where('keterangan', 'Pembayaran SPP demo pasca cut-off')
        ->get();
    expect($pembayaranDemo)->not->toBeEmpty();

    foreach ($pembayaranDemo as $pembayaran) {
        $jurnal = JurnalUmum::query()
            ->where('jenis_referensi', PembayaranJournalPoster::JENIS)
            ->where('referensi_id', $pembayaran->getKey())
            ->get();

        expect($jurnal)->toHaveCount(2)
            ->and((string) $jurnal->sum('debit'))->toBe((string) $jurnal->sum('kredit'));
    }

    // Slip gaji demo → akrual + KasKeluar terjurnal.
    $slipDemo = SlipGaji::query()->where('catatan', 'Slip gaji demo pasca cut-off')->get();
    expect($slipDemo)->not->toBeEmpty();

    foreach ($slipDemo as $slip) {
        expect($slip->status)->toBe('paid')
            ->and($slip->kas_keluar_id)->not->toBeNull();

        $akrual = JurnalUmum::query()
            ->where('jenis_referensi', SlipGajiJournalPoster::JENIS)
            ->where('referensi_id', $slip->getKey())
            ->count();
        expect($akrual)->toBeGreaterThanOrEqual(2);
    }

    // Tabungan demo → jurnal titipan nyata.
    $tabunganDemo = TabunganSiswa::query()
        ->where('keterangan', 'Tabungan demo pasca cut-off')
        ->get();
    expect($tabunganDemo)->not->toBeEmpty();

    foreach ($tabunganDemo as $row) {
        $jurnal = JurnalUmum::query()
            ->where('jenis_referensi', TabunganJournalPoster::JENIS)
            ->where('referensi_id', $row->getKey())
            ->count();
        expect($jurnal)->toBe(2);
    }
})->group('seeder-integrity');

it('runs DemoKeuanganPascaCutoffSeeder idempotently (no duplicate journals on re-run)', function () {
    $this->seed(DatabaseSeeder::class);

    $sebelum = JurnalUmum::query()->count();

    // Jalankan ulang hanya seeder demo: tidak boleh menambah jurnal apa pun.
    $this->seed(DemoKeuanganPascaCutoffSeeder::class);

    expect(JurnalUmum::query()->count())->toBe($sebelum);
})->group('seeder-integrity');
