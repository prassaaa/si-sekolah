<?php

use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use App\Services\Sarpras\PenyusutanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Aset garis lurus default: harga 12.000.000, residu 0, umur 120 bulan →
 * penyusutan 100.000/bulan, perolehan 2025-01-01.
 */
function asetSusut(array $overrides = []): SarprasBarang
{
    $kategori = SarprasKategori::query()->firstOrCreate(
        ['kode' => 'ELK'],
        ['nama' => 'Elektronik', 'is_active' => true],
    );

    return SarprasBarang::factory()->aset()->create(array_merge([
        'sarpras_kategori_id' => $kategori->id,
        'harga_perolehan' => 12000000,
        'nilai_residu' => 0,
        'metode_susut' => 'garis_lurus',
        'umur_ekonomis_bulan' => 120,
        'tanggal_perolehan' => Carbon::parse('2025-01-01'),
        'is_active' => true,
    ], $overrides));
}

// ─── (b1) Bulan berjalan dibulatkan ke bawah (integer) ───────────────────────

describe('Bulan berjalan integer (#88)', function () {
    it('membulatkan bulan berjalan ke bawah sehingga akumulasi = perBulan x floor(bulan)', function () {
        $barang = asetSusut();
        $service = app(PenyusutanService::class);

        // 2025-01-01 → 2025-03-15 = 2,45 bulan → floor 2 bulan.
        $akumulasi = $service->akumulasiSampai($barang, Carbon::parse('2025-03-15'));

        $perBulan = $service->penyusutanPerBulan($barang);
        expect($perBulan)->toBe('100000.00');

        // floor(2,45) = 2 → 200.000,00 (bukan 245.161,xx versi float lama).
        expect($akumulasi)->toBe(bcmul($perBulan, '2', 2))
            ->toBe('200000.00');
    });

    it('tidak menghitung pecahan bulan pertama (kurang dari sebulan penuh = 0)', function () {
        $barang = asetSusut();
        $service = app(PenyusutanService::class);

        // 2025-01-01 → 2025-01-20 = 0,x bulan → floor 0.
        expect($service->akumulasiSampai($barang, Carbon::parse('2025-01-20')))->toBe('0.00');
        expect($service->nilaiBuku($barang, Carbon::parse('2025-01-20')))->toBe('12000000.00');
    });

    it('akumulasi tepat pada batas bulan penuh sama dengan kelipatan perBulan', function () {
        $barang = asetSusut();
        $service = app(PenyusutanService::class);

        // 2025-01-01 → 2025-07-01 = tepat 6 bulan.
        expect($service->akumulasiSampai($barang, Carbon::parse('2025-07-01')))->toBe('600000.00');
    });
});

// ─── (b2) Saldo menurun (declining balance) benar & berbeda dari garis lurus ──

describe('Saldo menurun / declining balance (#88)', function () {
    /**
     * Aset saldo menurun: harga 12.000.000, residu 0, umur 50 bulan →
     * tarif 2/50 = 0,04 per bulan atas nilai buku berjalan.
     */
    function asetSaldoMenurun(array $overrides = []): SarprasBarang
    {
        return asetSusut(array_merge([
            'metode_susut' => 'saldo_menurun',
            'umur_ekonomis_bulan' => 50,
        ], $overrides));
    }

    it('menghitung penyusutan bulan pertama = tarif x harga perolehan (double declining)', function () {
        $barang = asetSaldoMenurun();
        $service = app(PenyusutanService::class);

        // Tarif 0,04 x 12.000.000 = 480.000,00 (≠ garis lurus 240.000).
        expect($service->penyusutanPerBulan($barang))->toBe('480000.00');
    });

    it('menghasilkan akumulasi berbeda dan lebih besar dari garis lurus di awal masa', function () {
        $sm = asetSaldoMenurun();
        $gl = asetSusut(['umur_ekonomis_bulan' => 50]);
        $service = app(PenyusutanService::class);

        $sampai = Carbon::parse('2025-04-01'); // tepat 3 bulan penuh.

        $akumSm = $service->akumulasiSampai($sm, $sampai);
        $akumGl = $service->akumulasiSampai($gl, $sampai);

        // Garis lurus: 240.000 x 3 = 720.000,00.
        expect($akumGl)->toBe('720000.00');

        // Saldo menurun (path-dependent, dihitung per bulan atas nilai buku):
        // m1 480.000 → m2 0,04x11.520.000=460.800 → m3 0,04x11.059.200=442.368
        // total = 1.383.168,00.
        expect($akumSm)->toBe('1383168.00');

        expect($akumSm)->not->toBe($akumGl);
        expect((float) $akumSm)->toBeGreaterThan((float) $akumGl);
    });

    it('nilai buku saldo menurun konsisten dengan akumulasinya', function () {
        $barang = asetSaldoMenurun();
        $service = app(PenyusutanService::class);

        $sampai = Carbon::parse('2025-04-01');
        $akumulasi = $service->akumulasiSampai($barang, $sampai);
        $nilaiBuku = $service->nilaiBuku($barang, $sampai);

        // 12.000.000 - 1.383.168 = 10.616.832,00.
        expect($nilaiBuku)->toBe(bcsub('12000000.00', $akumulasi, 2))
            ->toBe('10616832.00');
    });

    it('tidak pernah menyusut melewati base (harga - residu) untuk saldo menurun', function () {
        $barang = asetSaldoMenurun(['nilai_residu' => 1000000]);
        $service = app(PenyusutanService::class);

        // Jauh melewati masa manfaat → akumulasi mentok di base 11.000.000, nilai buku = residu.
        $jauh = Carbon::parse('2099-01-01');
        expect($service->akumulasiSampai($barang, $jauh))->toBe('11000000.00');
        expect($service->nilaiBuku($barang, $jauh))->toBe('1000000.00');
    });
});
