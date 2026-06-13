<?php

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use App\Models\SarprasPemeliharaan;
use App\Models\SarprasPeminjaman;
use App\Models\SarprasPenghapusan;
use App\Services\Sarpras\PenyusutanService;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Cut-off keputusan terkunci: 2026-07-01. Tanggal-tanggal di bawah ini
 * mengelilingi cut-off tersebut untuk menguji gate posting.
 */
const TANGGAL_SETELAH_CUTOFF = '2026-07-15';

const TANGGAL_SEBELUM_CUTOFF = '2026-06-15';

beforeEach(function () {
    config()->set('akuntansi.cutoff_posting', '2026-07-01');
    $this->seed(AkunSeeder::class);
});

/**
 * Aset tetap garis lurus dengan akumulasi penyusutan yang dapat diprediksi.
 * Perolehan 2025-01-01, harga 12.000.000, residu 0, umur 120 bulan →
 * penyusutan 100.000/bulan.
 */
function asetTetapSusut(array $overrides = []): SarprasBarang
{
    $kategori = SarprasKategori::factory()->create(['kode' => 'ELK', 'nama' => 'Elektronik']);

    return SarprasBarang::factory()->aset()->create(array_merge([
        'sarpras_kategori_id' => $kategori->id,
        'harga_perolehan' => 12000000,
        'nilai_residu' => 0,
        'metode_susut' => 'garis_lurus',
        'umur_ekonomis_bulan' => 120,
        'tanggal_perolehan' => Carbon::parse('2025-01-01'),
        'is_active' => true,
        'status' => 'tersedia',
    ], $overrides));
}

function kodeAkun(string $kode): int
{
    return (int) Akun::query()->where('kode', $kode)->value('id');
}

// ─── (a) Penghapusan aset → write-off seimbang ───────────────────────────────

describe('Penghapusan aset', function () {
    it('posts a balanced write-off journal at book value when approved after cutoff', function () {
        $barang = asetTetapSusut();

        $penghapusan = SarprasPenghapusan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SETELAH_CUTOFF,
            'status' => 'disetujui',
        ]);

        $rows = JurnalUmum::query()
            ->where('jenis_referensi', 'sarpras_penghapusan')
            ->where('referensi_id', $penghapusan->id)
            ->get();

        // Akumulasi diambil dari PenyusutanService per tanggal penghapusan;
        // nilai buku tersisa (perolehan - akumulasi) menjadi kerugian. Total
        // debit (akumulasi + kerugian) selalu sama dengan kredit (perolehan).
        $akumulasi = (float) app(PenyusutanService::class)
            ->akumulasiSampai($barang, Carbon::parse(TANGGAL_SETELAH_CUTOFF));
        $kerugian = 12000000.0 - $akumulasi;

        $totalDebit = $rows->sum(fn ($r) => (float) $r->debit);
        $totalKredit = $rows->sum(fn ($r) => (float) $r->kredit);

        expect($totalDebit)->toBe(12000000.0)
            ->and($totalKredit)->toBe(12000000.0)
            ->and($akumulasi)->toBeGreaterThan(0.0);

        $kreditRow = $rows->firstWhere('kredit', '>', 0);
        expect($kreditRow->akun_id)->toBe(kodeAkun('1-4001'))
            ->and((float) $kreditRow->kredit)->toBe(12000000.0);

        $debitAkum = $rows->first(fn ($r) => $r->akun_id === kodeAkun('1-4002'));
        $debitRugi = $rows->first(fn ($r) => $r->akun_id === kodeAkun('5-5002'));

        expect((float) $debitAkum->debit)->toBe($akumulasi)
            ->and((float) $debitRugi->debit)->toBe($kerugian);
    });

    it('falls back to full loss for non-depreciable assets (akumulasi 0)', function () {
        $barang = asetTetapSusut(['metode_susut' => 'tanpa']);

        $penghapusan = SarprasPenghapusan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SETELAH_CUTOFF,
            'status' => 'disetujui',
        ]);

        $rows = JurnalUmum::query()
            ->where('jenis_referensi', 'sarpras_penghapusan')
            ->where('referensi_id', $penghapusan->id)
            ->get();

        // Tanpa penyusutan: seluruh perolehan menjadi kerugian, tanpa baris akumulasi.
        expect($rows)->toHaveCount(2);

        $debitRugi = $rows->first(fn ($r) => $r->akun_id === kodeAkun('5-5002'));
        expect((float) $debitRugi->debit)->toBe(12000000.0);

        expect($rows->sum(fn ($r) => (float) $r->debit))
            ->toBe($rows->sum(fn ($r) => (float) $r->kredit));
    });

    it('does not post when approved before cutoff', function () {
        $barang = asetTetapSusut();

        SarprasPenghapusan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SEBELUM_CUTOFF,
            'status' => 'disetujui',
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_penghapusan')->count())->toBe(0);
    });

    it('is idempotent: re-saving an approved penghapusan posts only one set', function () {
        $barang = asetTetapSusut();

        $penghapusan = SarprasPenghapusan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SETELAH_CUTOFF,
            'status' => 'diajukan',
        ]);

        $penghapusan->update(['status' => 'disetujui']);
        $countAfterFirst = JurnalUmum::query()->where('jenis_referensi', 'sarpras_penghapusan')->count();

        // Transisi status berulang ke nilai sama tidak menambah jurnal.
        $penghapusan->update(['status' => 'disetujui']);
        $penghapusan->touch();

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_penghapusan')->count())
            ->toBe($countAfterFirst)
            ->toBeGreaterThan(0);
    });

    it('reverses the write-off when the penghapusan is deleted', function () {
        $barang = asetTetapSusut();

        $penghapusan = SarprasPenghapusan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SETELAH_CUTOFF,
            'status' => 'disetujui',
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_penghapusan')->count())->toBeGreaterThan(0);

        $penghapusan->delete();

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_penghapusan')->count())->toBe(0);
    });

    it('reverses when status is rolled back away from disetujui', function () {
        $barang = asetTetapSusut();

        $penghapusan = SarprasPenghapusan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SETELAH_CUTOFF,
            'status' => 'disetujui',
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_penghapusan')->count())->toBeGreaterThan(0);

        $penghapusan->update(['status' => 'ditolak']);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_penghapusan')->count())->toBe(0);
    });
});

// ─── (b) Pemeliharaan selesai dengan biaya → D Beban / K Kas ──────────────────

describe('Pemeliharaan', function () {
    it('posts beban pemeliharaan debit and kas credit when completed with cost after cutoff', function () {
        $barang = asetTetapSusut();

        $pemeliharaan = SarprasPemeliharaan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SETELAH_CUTOFF,
            'status' => 'dijadwalkan',
            'biaya' => 0,
        ]);

        $pemeliharaan->update([
            'status' => 'selesai',
            'tanggal_selesai' => TANGGAL_SETELAH_CUTOFF,
            'biaya' => 250000,
        ]);

        $rows = JurnalUmum::query()
            ->where('jenis_referensi', 'sarpras_pemeliharaan')
            ->where('referensi_id', $pemeliharaan->id)
            ->get();

        expect($rows)->toHaveCount(2);

        $debit = $rows->firstWhere('debit', '>', 0);
        $kredit = $rows->firstWhere('kredit', '>', 0);

        expect($debit->akun_id)->toBe(kodeAkun('5-3003'))
            ->and((float) $debit->debit)->toBe(250000.0)
            ->and($kredit->akun_id)->toBe(kodeAkun('1-1001'))
            ->and((float) $kredit->kredit)->toBe(250000.0);
    });

    it('does not post when completed with zero cost', function () {
        $barang = asetTetapSusut();

        $pemeliharaan = SarprasPemeliharaan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SETELAH_CUTOFF,
            'status' => 'dijadwalkan',
            'biaya' => 0,
        ]);

        $pemeliharaan->update([
            'status' => 'selesai',
            'tanggal_selesai' => TANGGAL_SETELAH_CUTOFF,
            'biaya' => 0,
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_pemeliharaan')->count())->toBe(0);
    });

    it('does not post when completed before cutoff', function () {
        $barang = asetTetapSusut();

        SarprasPemeliharaan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SEBELUM_CUTOFF,
            'tanggal_selesai' => TANGGAL_SEBELUM_CUTOFF,
            'status' => 'selesai',
            'biaya' => 250000,
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_pemeliharaan')->count())->toBe(0);
    });

    it('is idempotent across repeated saves while selesai', function () {
        $barang = asetTetapSusut();

        $pemeliharaan = SarprasPemeliharaan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SETELAH_CUTOFF,
            'tanggal_selesai' => TANGGAL_SETELAH_CUTOFF,
            'status' => 'selesai',
            'biaya' => 250000,
        ]);

        $first = JurnalUmum::query()->where('jenis_referensi', 'sarpras_pemeliharaan')->count();

        $pemeliharaan->update(['tindakan' => 'Catatan tambahan']);
        $pemeliharaan->update(['status' => 'selesai']);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_pemeliharaan')->count())
            ->toBe($first)
            ->toBe(2);
    });

    it('reverses when the pemeliharaan is deleted', function () {
        $barang = asetTetapSusut();

        $pemeliharaan = SarprasPemeliharaan::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal' => TANGGAL_SETELAH_CUTOFF,
            'tanggal_selesai' => TANGGAL_SETELAH_CUTOFF,
            'status' => 'selesai',
            'biaya' => 250000,
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_pemeliharaan')->count())->toBe(2);

        $pemeliharaan->delete();

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_pemeliharaan')->count())->toBe(0);
    });
});

// ─── (c) Denda pengembalian → D Kas / K Pendapatan Denda ──────────────────────

describe('Denda peminjaman', function () {
    it('posts kas debit and pendapatan denda credit when a fine is recorded after cutoff', function () {
        $barang = asetTetapSusut();

        $peminjaman = SarprasPeminjaman::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal_pinjam' => TANGGAL_SETELAH_CUTOFF,
            'tanggal_harus_kembali' => '2026-07-20',
            'status' => 'dipinjam',
        ]);

        $peminjaman->update([
            'status' => 'terlambat',
            'tanggal_kembali' => '2026-07-25',
            'hari_telat' => 5,
            'denda' => 15000,
        ]);

        $rows = JurnalUmum::query()
            ->where('jenis_referensi', 'sarpras_denda')
            ->where('referensi_id', $peminjaman->id)
            ->get();

        expect($rows)->toHaveCount(2);

        $debit = $rows->firstWhere('debit', '>', 0);
        $kredit = $rows->firstWhere('kredit', '>', 0);

        expect($debit->akun_id)->toBe(kodeAkun('1-1001'))
            ->and((float) $debit->debit)->toBe(15000.0)
            ->and($kredit->akun_id)->toBe(kodeAkun('4-1006'))
            ->and((float) $kredit->kredit)->toBe(15000.0);
    });

    it('does not post when there is no fine', function () {
        $barang = asetTetapSusut();

        $peminjaman = SarprasPeminjaman::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal_pinjam' => TANGGAL_SETELAH_CUTOFF,
            'tanggal_harus_kembali' => '2026-07-20',
            'status' => 'dipinjam',
        ]);

        $peminjaman->update([
            'status' => 'dikembalikan',
            'tanggal_kembali' => '2026-07-18',
            'denda' => 0,
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_denda')->count())->toBe(0);
    });

    it('does not post when the fine is recorded before cutoff', function () {
        $barang = asetTetapSusut();

        $peminjaman = SarprasPeminjaman::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal_pinjam' => TANGGAL_SEBELUM_CUTOFF,
            'tanggal_harus_kembali' => '2026-06-20',
            'status' => 'dipinjam',
        ]);

        $peminjaman->update([
            'status' => 'terlambat',
            'tanggal_kembali' => '2026-06-25',
            'hari_telat' => 5,
            'denda' => 15000,
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_denda')->count())->toBe(0);
    });

    it('is idempotent across repeated saves with the same fine', function () {
        $barang = asetTetapSusut();

        $peminjaman = SarprasPeminjaman::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal_pinjam' => TANGGAL_SETELAH_CUTOFF,
            'tanggal_harus_kembali' => '2026-07-20',
            'status' => 'dipinjam',
        ]);

        $peminjaman->update([
            'status' => 'terlambat',
            'tanggal_kembali' => '2026-07-25',
            'denda' => 15000,
        ]);

        $first = JurnalUmum::query()->where('jenis_referensi', 'sarpras_denda')->count();

        $peminjaman->update(['catatan' => 'Sudah ditegur']);
        $peminjaman->update(['status' => 'terlambat']);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_denda')->count())
            ->toBe($first)
            ->toBe(2);
    });

    it('reverses when the peminjaman is deleted', function () {
        $barang = asetTetapSusut();

        $peminjaman = SarprasPeminjaman::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal_pinjam' => TANGGAL_SETELAH_CUTOFF,
            'tanggal_harus_kembali' => '2026-07-20',
            'status' => 'dipinjam',
        ]);

        $peminjaman->update([
            'status' => 'terlambat',
            'tanggal_kembali' => '2026-07-25',
            'denda' => 15000,
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_denda')->count())->toBe(2);

        $peminjaman->delete();

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_denda')->count())->toBe(0);
    });

    it('SAFE-skips posting when required akun are missing', function () {
        Akun::query()->where('kode', '4-1006')->delete();

        $barang = asetTetapSusut();

        $peminjaman = SarprasPeminjaman::factory()->create([
            'sarpras_barang_id' => $barang->id,
            'tanggal_pinjam' => TANGGAL_SETELAH_CUTOFF,
            'tanggal_harus_kembali' => '2026-07-20',
            'status' => 'dipinjam',
        ]);

        $peminjaman->update([
            'status' => 'terlambat',
            'tanggal_kembali' => '2026-07-25',
            'denda' => 15000,
        ]);

        expect(JurnalUmum::query()->where('jenis_referensi', 'sarpras_denda')->count())->toBe(0);
        // Pencatatan peminjaman tetap berjalan meski jurnal dilewati.
        expect($peminjaman->fresh()->denda)->not->toBeNull();
    });
});
