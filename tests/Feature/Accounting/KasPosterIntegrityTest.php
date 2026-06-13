<?php

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Services\Accounting\KasJournalPoster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

/**
 * Helper: buat akun Kas tunai default (kode 1-1001).
 */
function buatAkunKasTunai(): Akun
{
    return Akun::factory()->aset()->create([
        'kode' => '1-1001',
        'nama' => 'Kas',
        'kategori' => 'lancar',
    ]);
}

/**
 * Helper: buat akun Bank BCA (kode 1-1002).
 */
function buatAkunBankBca(): Akun
{
    return Akun::factory()->aset()->create([
        'kode' => '1-1002',
        'nama' => 'Bank BCA',
        'kategori' => 'lancar',
    ]);
}

// ─────────────────────────────────────────────────────────────────────────────
// (a) kas_akun_id = Bank BCA → jurnal debit BCA, kredit akun lawan
// ─────────────────────────────────────────────────────────────────────────────

it('kas masuk dengan kas_akun_id Bank BCA memposting debit ke BCA dan kredit ke akun lawan', function () {
    buatAkunKasTunai();
    $bca = buatAkunBankBca();
    $pendapatan = Akun::factory()->pendapatan()->create();

    $kas = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        'kas_akun_id' => $bca->id,
        'tanggal' => '2026-06-13',
        'nominal' => 1_500_000,
        'sumber' => 'SPP via BCA',
    ]);

    $entri = JurnalUmum::query()
        ->where('jenis_referensi', KasJournalPoster::JENIS_KAS_MASUK)
        ->where('referensi_id', $kas->id)
        ->get();

    expect($entri)->toHaveCount(2);

    $debitRow = $entri->firstWhere('akun_id', $bca->id);
    $kreditRow = $entri->firstWhere('akun_id', $pendapatan->id);

    expect($debitRow)->not->toBeNull()
        ->and($kreditRow)->not->toBeNull()
        ->and((float) $debitRow->debit)->toBe(1_500_000.0)
        ->and((float) $kreditRow->kredit)->toBe(1_500_000.0)
        ->and((float) $entri->sum('debit'))->toBe((float) $entri->sum('kredit'));
});

it('kas keluar dengan kas_akun_id Bank BCA memposting debit ke akun lawan dan kredit ke BCA', function () {
    buatAkunKasTunai();
    $bca = buatAkunBankBca();
    $beban = Akun::factory()->beban()->create();

    $kas = KasKeluar::create([
        'akun_id' => $beban->id,
        'kas_akun_id' => $bca->id,
        'tanggal' => '2026-06-13',
        'nominal' => 500_000,
        'penerima' => 'PLN',
    ]);

    $entri = JurnalUmum::query()
        ->where('jenis_referensi', KasJournalPoster::JENIS_KAS_KELUAR)
        ->where('referensi_id', $kas->id)
        ->get();

    expect($entri)->toHaveCount(2);

    $kreditRow = $entri->firstWhere('akun_id', $bca->id);
    $debitRow = $entri->firstWhere('akun_id', $beban->id);

    expect($debitRow)->not->toBeNull()
        ->and($kreditRow)->not->toBeNull()
        ->and((float) $debitRow->debit)->toBe(500_000.0)
        ->and((float) $kreditRow->kredit)->toBe(500_000.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (b) Tanpa kas_akun_id → fallback ke 1-1001
// ─────────────────────────────────────────────────────────────────────────────

it('tanpa kas_akun_id, poster fallback ke akun kode 1-1001', function () {
    $kasTunai = buatAkunKasTunai();
    $pendapatan = Akun::factory()->pendapatan()->create();

    $kas = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        // kas_akun_id sengaja tidak diset
        'tanggal' => '2026-06-13',
        'nominal' => 750_000,
    ]);

    $entri = JurnalUmum::query()
        ->where('jenis_referensi', KasJournalPoster::JENIS_KAS_MASUK)
        ->where('referensi_id', $kas->id)
        ->get();

    expect($entri)->toHaveCount(2);

    $debitRow = $entri->firstWhere('akun_id', $kasTunai->id);

    expect($debitRow)->not->toBeNull()
        ->and((float) $debitRow->debit)->toBe(750_000.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (c) Akun lawan == akun kas → ditolak ValidationException, tidak ada baris
// ─────────────────────────────────────────────────────────────────────────────

it('menolak dengan ValidationException bila akun_id sama dengan kas_akun_id', function () {
    $kasTunai = buatAkunKasTunai();

    expect(fn () => KasMasuk::create([
        'akun_id' => $kasTunai->id,      // sengaja sama
        'kas_akun_id' => $kasTunai->id,  // dengan kas_akun_id
        'tanggal' => '2026-06-13',
        'nominal' => 300_000,
    ]))->toThrow(ValidationException::class);

    expect(KasMasuk::count())->toBe(0)
        ->and(JurnalUmum::count())->toBe(0);
});

it('tidak membuat row kas maupun jurnal bila validasi debit==kredit gagal', function () {
    $kasTunai = buatAkunKasTunai();

    try {
        KasKeluar::create([
            'akun_id' => $kasTunai->id,
            'kas_akun_id' => $kasTunai->id,
            'tanggal' => '2026-06-13',
            'nominal' => 200_000,
        ]);
    } catch (ValidationException) {
        // expected
    }

    expect(KasKeluar::count())->toBe(0)
        ->and(JurnalUmum::count())->toBe(0);
});

// ─────────────────────────────────────────────────────────────────────────────
// (d) Update kas masuk → jurnal lama ter-reverse + repost benar
// ─────────────────────────────────────────────────────────────────────────────

it('update kas masuk me-reverse jurnal lama dan memposting jurnal baru dengan nominal baru', function () {
    $kasTunai = buatAkunKasTunai();
    $pendapatan = Akun::factory()->pendapatan()->create();

    $kas = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        'kas_akun_id' => $kasTunai->id,
        'tanggal' => '2026-06-13',
        'nominal' => 500_000,
    ]);

    // Jurnal pertama harus ada
    expect(JurnalUmum::where('referensi_id', $kas->id)->count())->toBe(2);

    $kas->update(['nominal' => 900_000]);

    // Setelah update, hanya 2 entri aktif (reversed lama, 2 baru)
    $aktif = JurnalUmum::query()
        ->where('jenis_referensi', KasJournalPoster::JENIS_KAS_MASUK)
        ->where('referensi_id', $kas->id)
        ->get();

    expect($aktif)->toHaveCount(2)
        ->and((float) $aktif->where('akun_id', $kasTunai->id)->first()->debit)->toBe(900_000.0)
        ->and((float) $aktif->where('akun_id', $pendapatan->id)->first()->kredit)->toBe(900_000.0)
        ->and((float) $aktif->sum('debit'))->toBe((float) $aktif->sum('kredit'));

    // Entri lama masih ada (soft-deleted)
    expect(JurnalUmum::withTrashed()->where('referensi_id', $kas->id)->count())->toBe(4);
});

// ─────────────────────────────────────────────────────────────────────────────
// (e) Delete → jurnal ter-reverse (soft-delete)
// ─────────────────────────────────────────────────────────────────────────────

it('delete kas masuk me-reverse semua entri jurnal terkait', function () {
    $kasTunai = buatAkunKasTunai();
    $pendapatan = Akun::factory()->pendapatan()->create();

    $kas = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        'kas_akun_id' => $kasTunai->id,
        'tanggal' => '2026-06-13',
        'nominal' => 300_000,
    ]);

    expect(JurnalUmum::where('referensi_id', $kas->id)->count())->toBe(2);

    $kas->delete();

    expect(JurnalUmum::where('referensi_id', $kas->id)->count())->toBe(0)
        ->and(JurnalUmum::withTrashed()->where('referensi_id', $kas->id)->count())->toBe(2);
});

it('delete kas keluar me-reverse semua entri jurnal terkait', function () {
    $kasTunai = buatAkunKasTunai();
    $beban = Akun::factory()->beban()->create();

    $kas = KasKeluar::create([
        'akun_id' => $beban->id,
        'kas_akun_id' => $kasTunai->id,
        'tanggal' => '2026-06-13',
        'nominal' => 200_000,
        'penerima' => 'Vendor',
    ]);

    $kas->delete();

    expect(JurnalUmum::where('referensi_id', $kas->id)->count())->toBe(0)
        ->and(JurnalUmum::withTrashed()->where('referensi_id', $kas->id)->count())->toBe(2);
});

// ─────────────────────────────────────────────────────────────────────────────
// (f) Dua create berurutan → nomor_bukti unik & sekuensial
// ─────────────────────────────────────────────────────────────────────────────

it('dua create kas masuk berurutan menghasilkan nomor_bukti unik dan sekuensial', function () {
    $kasTunai = buatAkunKasTunai();
    $pendapatan = Akun::factory()->pendapatan()->create();

    $kas1 = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        'kas_akun_id' => $kasTunai->id,
        'tanggal' => '2026-06-13',
        'nominal' => 100_000,
    ]);

    $kas2 = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        'kas_akun_id' => $kasTunai->id,
        'tanggal' => '2026-06-13',
        'nominal' => 200_000,
    ]);

    expect($kas1->nomor_bukti)->not->toBe($kas2->nomor_bukti);

    $prefix = 'KM-'.date('Ymd');
    expect($kas1->nomor_bukti)->toStartWith($prefix)
        ->and($kas2->nomor_bukti)->toStartWith($prefix);

    // Nomor urut berbeda 1
    $seq1 = (int) substr($kas1->nomor_bukti, -4);
    $seq2 = (int) substr($kas2->nomor_bukti, -4);

    expect($seq2 - $seq1)->toBe(1);
});

it('dua create kas keluar berurutan menghasilkan nomor_bukti unik dan sekuensial', function () {
    $kasTunai = buatAkunKasTunai();
    $beban = Akun::factory()->beban()->create();

    $kas1 = KasKeluar::create([
        'akun_id' => $beban->id,
        'kas_akun_id' => $kasTunai->id,
        'tanggal' => '2026-06-13',
        'nominal' => 100_000,
        'penerima' => 'A',
    ]);

    $kas2 = KasKeluar::create([
        'akun_id' => $beban->id,
        'kas_akun_id' => $kasTunai->id,
        'tanggal' => '2026-06-13',
        'nominal' => 200_000,
        'penerima' => 'B',
    ]);

    expect($kas1->nomor_bukti)->not->toBe($kas2->nomor_bukti);

    $seq1 = (int) substr($kas1->nomor_bukti, -4);
    $seq2 = (int) substr($kas2->nomor_bukti, -4);

    expect($seq2 - $seq1)->toBe(1);
});
