<?php

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use App\Models\SarprasPengadaan;
use App\Models\SarprasPengadaanItem;
use App\Services\Sarpras\PenyusutanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Seed the minimal chart-of-accounts the posters resolve by convention.
 */
function seedAkun(): void
{
    $akuns = [
        ['kode' => '1-1001', 'nama' => 'Kas', 'tipe' => 'aset', 'kategori' => 'lancar', 'posisi_normal' => 'debit'],
        ['kode' => '1-3001', 'nama' => 'Perlengkapan', 'tipe' => 'aset', 'kategori' => 'lancar', 'posisi_normal' => 'debit'],
        ['kode' => '1-4001', 'nama' => 'Peralatan', 'tipe' => 'aset', 'kategori' => 'tetap', 'posisi_normal' => 'debit'],
        ['kode' => '1-4002', 'nama' => 'Akumulasi Penyusutan Peralatan', 'tipe' => 'aset', 'kategori' => 'tetap', 'posisi_normal' => 'kredit'],
        ['kode' => '5-4001', 'nama' => 'Beban Penyusutan', 'tipe' => 'beban', 'kategori' => 'non_operasional', 'posisi_normal' => 'debit'],
    ];

    foreach ($akuns as $a) {
        Akun::query()->create($a + ['level' => 1, 'saldo_awal' => 0, 'saldo_akhir' => 0, 'is_active' => true]);
    }
}

function asetGarisLurus(array $overrides = []): SarprasBarang
{
    $kategori = SarprasKategori::factory()->create(['kode' => 'ELK', 'nama' => 'Elektronik']);

    return SarprasBarang::factory()->create(array_merge([
        'sarpras_kategori_id' => $kategori->id,
        'tipe' => 'aset',
        'harga_perolehan' => 10000000,
        'nilai_residu' => 1000000,
        'metode_susut' => 'garis_lurus',
        'umur_ekonomis_bulan' => 90,
        'tanggal_perolehan' => Carbon::parse('2025-01-01'),
        'is_active' => true,
    ], $overrides));
}

describe('PenyusutanService math (garis lurus)', function () {
    it('computes depreciation per month', function () {
        $barang = asetGarisLurus();
        $service = app(PenyusutanService::class);

        // (10.000.000 - 1.000.000) / 90 = 100.000,00
        expect($service->penyusutanPerBulan($barang))->toBe('100000.00');
    });

    it('computes book value after some months', function () {
        $barang = asetGarisLurus();
        $service = app(PenyusutanService::class);

        // 6 full months from 2025-01-01 to 2025-07-01 -> 600.000 akumulasi.
        $akumulasi = $service->akumulasiSampai($barang, Carbon::parse('2025-07-01'));
        expect($akumulasi)->toBe('600000.00');

        $nilaiBuku = $service->nilaiBuku($barang, Carbon::parse('2025-07-01'));
        expect($nilaiBuku)->toBe('9400000.00');
    });

    it('floors book value at residual and caps accumulation', function () {
        $barang = asetGarisLurus();
        $service = app(PenyusutanService::class);

        // Way past full life: akumulasi capped at base (9.000.000), book value at residu.
        $far = Carbon::parse('2099-01-01');
        expect($service->akumulasiSampai($barang, $far))->toBe('9000000.00');
        expect($service->nilaiBuku($barang, $far))->toBe('1000000.00');
    });

    it('returns zero for non-depreciable items', function () {
        $barang = asetGarisLurus(['metode_susut' => 'tanpa']);
        $service = app(PenyusutanService::class);

        expect($service->penyusutanPerBulan($barang))->toBe('0.00');
        expect($service->akumulasiSampai($barang, Carbon::parse('2025-07-01')))->toBe('0.00');
    });

    it('uses kategori default umur when none set', function () {
        $barang = asetGarisLurus(['umur_ekonomis_bulan' => null]);

        // ELK default = 48 months. (10jt - 1jt)/48 = 187.500,00
        expect(app(PenyusutanService::class)->penyusutanPerBulan($barang))->toBe('187500.00');
    });
});

describe('Pengadaan terima posts balanced jurnal', function () {
    it('posts a balanced procurement journal when accounts exist', function () {
        seedAkun();

        $pengadaan = SarprasPengadaan::factory()->create(['status' => 'disetujui', 'sumber_dana' => 'bos']);
        SarprasPengadaanItem::factory()->create([
            'sarpras_pengadaan_id' => $pengadaan->id,
            'jumlah' => 2,
            'harga_satuan' => 500000,
            'subtotal' => bcmul('2', '500000', 2),
        ]);
        $pengadaan->recalculateTotal();

        $pengadaan->terima();

        $rows = JurnalUmum::query()->where('jenis_referensi', 'sarpras_pengadaan')->get();
        expect($rows)->toHaveCount(2);

        $totalDebit = $rows->sum(fn ($r) => (float) $r->debit);
        $totalKredit = $rows->sum(fn ($r) => (float) $r->kredit);
        expect($totalDebit)->toBe(1000000.0);
        expect($totalKredit)->toBe(1000000.0);

        // Item bertipe 'bahan' (default terima()) → debit ke Perlengkapan (1-3001), bukan Aset Tetap.
        $debitRow = $rows->firstWhere('debit', '>', 0);
        expect($debitRow->akun_id)->toBe(Akun::where('kode', '1-3001')->value('id'));
    });

    it('SAFE-skips posting when accounts are missing (no exception, no partial post)', function () {
        // No akun seeded.
        $pengadaan = SarprasPengadaan::factory()->create(['status' => 'disetujui']);
        SarprasPengadaanItem::factory()->create([
            'sarpras_pengadaan_id' => $pengadaan->id,
            'jumlah' => 1,
            'harga_satuan' => 750000,
            'subtotal' => bcmul('1', '750000', 2),
        ]);
        $pengadaan->recalculateTotal();

        $pengadaan->terima();

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_pengadaan')->count())->toBe(0);
        // Stock intake still happened.
        expect($pengadaan->fresh()->status)->toBe('diterima');
        expect(SarprasBarang::query()->count())->toBeGreaterThan(0);
    });

    it('is idempotent: receiving twice posts only one journal pair', function () {
        seedAkun();

        $pengadaan = SarprasPengadaan::factory()->create(['status' => 'disetujui']);
        SarprasPengadaanItem::factory()->create([
            'sarpras_pengadaan_id' => $pengadaan->id,
            'jumlah' => 1,
            'harga_satuan' => 1000000,
            'subtotal' => bcmul('1', '1000000', 2),
        ]);
        $pengadaan->recalculateTotal();

        $pengadaan->terima();
        $pengadaan->terima();

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_pengadaan')->count())->toBe(2);
    });
});

describe('SusutBulanan command', function () {
    it('posts a balanced depreciation journal and is idempotent', function () {
        seedAkun();
        $barang = asetGarisLurus();

        $this->artisan('sarpras:susut-bulanan', ['--periode' => '2025-03'])
            ->assertSuccessful();

        $rows = JurnalUmum::query()->where('jenis_referensi', 'sarpras_penyusutan')->get();
        expect($rows)->toHaveCount(2);
        expect($rows->sum(fn ($r) => (float) $r->debit))->toBe(100000.0);
        expect($rows->sum(fn ($r) => (float) $r->kredit))->toBe(100000.0);

        $debitRow = $rows->firstWhere('debit', '>', 0);
        expect($debitRow->akun_id)->toBe(Akun::where('kode', '5-4001')->value('id'));

        // Running again for the same period posts nothing new.
        $this->artisan('sarpras:susut-bulanan', ['--periode' => '2025-03'])
            ->assertSuccessful();

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_penyusutan')->count())->toBe(2);
    });

    it('SAFE-skips depreciation posting when accounts are missing', function () {
        $barang = asetGarisLurus();

        $this->artisan('sarpras:susut-bulanan', ['--periode' => '2025-03'])
            ->assertSuccessful();

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_penyusutan')->count())->toBe(0);
    });

    it('does not post for non-depreciable assets', function () {
        seedAkun();
        asetGarisLurus(['metode_susut' => 'tanpa']);

        $this->artisan('sarpras:susut-bulanan', ['--periode' => '2025-03'])
            ->assertSuccessful();

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_penyusutan')->count())->toBe(0);
    });
});
