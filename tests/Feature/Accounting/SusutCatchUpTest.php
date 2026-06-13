<?php

use App\Models\JurnalUmum;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use App\Services\Sarpras\SarprasJournalPoster;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

/**
 * Cut-off terkunci 2026-07-01. Catch-up tidak boleh membackfill bulan sebelum
 * cut-off, dan harus mengejar setiap bulan dari cut-off s/d bulan berjalan.
 */
beforeEach(function () {
    config()->set('akuntansi.cutoff_posting', '2026-07-01');
    $this->seed(AkunSeeder::class);
});

/**
 * Aset tetap garis lurus: perolehan jauh sebelum cut-off, residu 0, umur 120
 * bulan, harga 12.000.000 → penyusutan 100.000/bulan penuh.
 */
function asetSusutCatchUp(array $overrides = []): SarprasBarang
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

/**
 * @return Collection<int, JurnalUmum>
 */
function jurnalPenyusutan(): Collection
{
    return JurnalUmum::query()
        ->where('jenis_referensi', SarprasJournalPoster::JENIS_PENYUSUTAN)
        ->get();
}

/**
 * @return list<string> daftar marker periode unik (YYYY-MM) dari referensi.
 */
function periodeTerposting(): array
{
    return JurnalUmum::query()
        ->where('jenis_referensi', SarprasJournalPoster::JENIS_PENYUSUTAN)
        ->pluck('referensi')
        ->map(fn (string $r): string => substr($r, strlen('SUSUT-')))
        ->unique()
        ->sort()
        ->values()
        ->all();
}

it('catches up every month from the cut-off through the current month on a cold run', function () {
    asetSusutCatchUp();

    // Server "menyala" di Oktober 2026: cut-off Juli → harus mengejar Jul, Agu, Sep, Okt.
    $this->travelTo(Carbon::parse('2026-10-10'));

    $this->artisan('sarpras:susut-bulanan')->assertSuccessful();

    expect(periodeTerposting())->toBe(['2026-07', '2026-08', '2026-09', '2026-10']);

    // Tiap periode = pasangan seimbang 100.000.
    $rows = jurnalPenyusutan();
    expect($rows)->toHaveCount(8)
        ->and($rows->sum(fn ($r) => (float) $r->debit))->toBe(400000.0)
        ->and($rows->sum(fn ($r) => (float) $r->kredit))->toBe(400000.0);
});

it('never backfills periods before the cut-off', function () {
    asetSusutCatchUp();

    $this->travelTo(Carbon::parse('2026-08-05'));

    $this->artisan('sarpras:susut-bulanan')->assertSuccessful();

    // Aset diperoleh 2025-01 tetapi catch-up hanya dari cut-off (2026-07).
    expect(periodeTerposting())->toBe(['2026-07', '2026-08'])
        ->and(JurnalUmum::query()->where('referensi', 'SUSUT-2026-06')->exists())->toBeFalse()
        ->and(JurnalUmum::query()->where('referensi', 'SUSUT-2025-01')->exists())->toBeFalse();
});

it('is idempotent: running the catch-up twice posts nothing the second time', function () {
    asetSusutCatchUp();

    $this->travelTo(Carbon::parse('2026-09-20'));

    $this->artisan('sarpras:susut-bulanan')->assertSuccessful();
    $jumlahPertama = jurnalPenyusutan()->count();

    $this->artisan('sarpras:susut-bulanan')->assertSuccessful();
    $jumlahKedua = jurnalPenyusutan()->count();

    expect($jumlahPertama)->toBe(6) // Jul, Agu, Sep × pasangan
        ->and($jumlahKedua)->toBe($jumlahPertama)
        ->and(periodeTerposting())->toBe(['2026-07', '2026-08', '2026-09']);
});

it('resumes from the last posted period without re-posting earlier months', function () {
    asetSusutCatchUp();

    // Bulan 1: server menyala Agustus, mengejar Jul + Agu.
    $this->travelTo(Carbon::parse('2026-08-15'));
    $this->artisan('sarpras:susut-bulanan')->assertSuccessful();
    expect(periodeTerposting())->toBe(['2026-07', '2026-08']);

    $idSebelum = jurnalPenyusutan()->pluck('id')->sort()->values()->all();

    // Bulan 2: waktu maju ke November, hanya Sep–Nov yang baru.
    $this->travelTo(Carbon::parse('2026-11-03'));
    $this->artisan('sarpras:susut-bulanan')->assertSuccessful();

    expect(periodeTerposting())->toBe(['2026-07', '2026-08', '2026-09', '2026-10', '2026-11']);

    // Jurnal Jul & Agu tidak disentuh ulang (id-nya tetap ada, tidak diganti).
    $idSesudah = jurnalPenyusutan()->pluck('id')->all();
    foreach ($idSebelum as $id) {
        expect(in_array($id, $idSesudah, true))->toBeTrue();
    }
});

it('with --periode posts only that month and skips catch-up entirely', function () {
    asetSusutCatchUp();

    $this->travelTo(Carbon::parse('2026-10-10'));

    $this->artisan('sarpras:susut-bulanan', ['--periode' => '2026-09'])->assertSuccessful();

    expect(periodeTerposting())->toBe(['2026-09'])
        ->and(jurnalPenyusutan())->toHaveCount(2);
});

it('with --no-catchup processes only the current month', function () {
    asetSusutCatchUp();

    $this->travelTo(Carbon::parse('2026-10-10'));

    $this->artisan('sarpras:susut-bulanan', ['--no-catchup' => true])->assertSuccessful();

    expect(periodeTerposting())->toBe(['2026-10'])
        ->and(jurnalPenyusutan())->toHaveCount(2);
});

it('catches up nothing for an asset already fully depreciated before the cut-off', function () {
    // Perolehan 2025-01, umur 2 bulan → akumulasi mentok di base sejak 2025-03,
    // jauh sebelum cut-off. Catch-up Jul–Des tidak boleh memposting apa pun.
    asetSusutCatchUp([
        'tanggal_perolehan' => Carbon::parse('2025-01-01'),
        'harga_perolehan' => 200000,
        'nilai_residu' => 0,
        'umur_ekonomis_bulan' => 2,
    ]);

    $this->travelTo(Carbon::parse('2026-12-10'));

    $this->artisan('sarpras:susut-bulanan')->assertSuccessful();

    expect(jurnalPenyusutan())->toHaveCount(0);
});

it('keeps each catch-up period balanced over a long run', function () {
    asetSusutCatchUp(); // 100.000/bulan, umur 120 bulan → tidak akan mentok.

    $this->travelTo(Carbon::parse('2027-06-10')); // Jul 2026 .. Jun 2027 = 12 bulan.

    $this->artisan('sarpras:susut-bulanan')->assertSuccessful();

    expect(periodeTerposting())->toHaveCount(12);

    $rows = jurnalPenyusutan();
    // Tiap periode tetap seimbang: total debit == total kredit == 12 × 100.000.
    expect($rows->sum(fn ($r) => (float) $r->debit))->toBe(1200000.0)
        ->and($rows->sum(fn ($r) => (float) $r->kredit))->toBe(1200000.0);
});
