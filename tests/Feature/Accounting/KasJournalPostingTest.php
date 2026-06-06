<?php

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Services\Accounting\KasJournalPoster;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeCashAkun(): Akun
{
    return Akun::factory()->aset()->create([
        'kode' => '1-1001',
        'nama' => 'Kas',
        'kategori' => 'lancar',
    ]);
}

it('posts a balanced debit/credit pair when kas masuk is created', function () {
    $cash = makeCashAkun();
    $pendapatan = Akun::factory()->pendapatan()->create();

    $kas = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        'tanggal' => '2026-01-10',
        'nominal' => 750000,
        'sumber' => 'SPP',
    ]);

    $entries = JurnalUmum::where('jenis_referensi', KasJournalPoster::JENIS_KAS_MASUK)
        ->where('referensi_id', $kas->id)
        ->get();

    expect($entries)->toHaveCount(2)
        ->and((string) $entries->sum('debit'))->toBe((string) $entries->sum('kredit'));

    $debitRow = $entries->firstWhere('akun_id', $cash->id);
    $kreditRow = $entries->firstWhere('akun_id', $pendapatan->id);

    expect((float) $debitRow->debit)->toBe(750000.0)
        ->and((float) $kreditRow->kredit)->toBe(750000.0);
});

it('posts cash on the credit side for kas keluar', function () {
    $cash = makeCashAkun();
    $beban = Akun::factory()->beban()->create();

    $kas = KasKeluar::create([
        'akun_id' => $beban->id,
        'tanggal' => '2026-01-10',
        'nominal' => 300000,
        'penerima' => 'PLN',
    ]);

    $entries = JurnalUmum::where('jenis_referensi', KasJournalPoster::JENIS_KAS_KELUAR)
        ->where('referensi_id', $kas->id)
        ->get();

    expect($entries)->toHaveCount(2);

    $cashRow = $entries->firstWhere('akun_id', $cash->id);
    $bebanRow = $entries->firstWhere('akun_id', $beban->id);

    expect((float) $cashRow->kredit)->toBe(300000.0)
        ->and((float) $bebanRow->debit)->toBe(300000.0);
});

it('does not post and does not break saving when no cash akun is resolvable', function () {
    $pendapatan = Akun::factory()->pendapatan()->create();

    $kas = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        'tanggal' => '2026-01-10',
        'nominal' => 500000,
    ]);

    expect($kas->exists)->toBeTrue()
        ->and(JurnalUmum::where('referensi_id', $kas->id)->count())->toBe(0);
});

it('reverses journal entries when the kas row is deleted', function () {
    makeCashAkun();
    $pendapatan = Akun::factory()->pendapatan()->create();

    $kas = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        'tanggal' => '2026-01-10',
        'nominal' => 500000,
    ]);

    expect(JurnalUmum::where('referensi_id', $kas->id)->count())->toBe(2);

    $kas->delete();

    expect(JurnalUmum::where('referensi_id', $kas->id)->count())->toBe(0)
        ->and(JurnalUmum::withTrashed()->where('referensi_id', $kas->id)->count())->toBe(2);
});

it('reverses and reposts on update without double-posting', function () {
    makeCashAkun();
    $pendapatan = Akun::factory()->pendapatan()->create();

    $kas = KasMasuk::create([
        'akun_id' => $pendapatan->id,
        'tanggal' => '2026-01-10',
        'nominal' => 500000,
    ]);

    $kas->update(['nominal' => 800000]);

    $active = JurnalUmum::where('referensi_id', $kas->id)->get();

    expect($active)->toHaveCount(2)
        ->and((float) $active->where('akun_id', $pendapatan->id)->first()->kredit)->toBe(800000.0);
});
