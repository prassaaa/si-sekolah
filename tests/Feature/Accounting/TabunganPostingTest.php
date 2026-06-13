<?php

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Siswa;
use App\Models\TabunganSiswa;
use App\Services\Accounting\TabunganJournalPoster;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(AkunSeeder::class);
});

/**
 * Buat baris tabungan langsung via model (tanpa Filament page).
 * Penarikan butuh DB::transaction agar lockForUpdate pada
 * assertWithdrawalIsCovered aktif (sama seperti TabunganPembayaranIntegrityTest).
 */
function buatTabunganPosting(Siswa $siswa, string $jenis, float $nominal, string $tanggal): TabunganSiswa
{
    return DB::transaction(fn () => TabunganSiswa::create([
        'siswa_id' => $siswa->id,
        'jenis' => $jenis,
        'nominal' => $nominal,
        'tanggal' => $tanggal,
    ]));
}

/**
 * Saldo akun titipan (2-1004) dihitung murni dari jurnal:
 * akun kewajiban posisi normal kredit → saldo = SUM(kredit) - SUM(debit).
 */
function saldoTitipanDariJurnal(): string
{
    $akunId = Akun::query()->where('kode', '2-1004')->value('id');

    $kredit = (string) JurnalUmum::query()->where('akun_id', $akunId)->sum('kredit');
    $debit = (string) JurnalUmum::query()->where('akun_id', $akunId)->sum('debit');

    return bcsub($kredit, $debit, 2);
}

// ---------------------------------------------------------------------------
// (a) Setor >= cut-off → 2 baris seimbang D Kas / K Titipan
// ---------------------------------------------------------------------------

it('(a) memposting setor sebagai D Kas / K Titipan saat tanggal >= cut-off', function () {
    $siswa = Siswa::factory()->create();

    $tabungan = buatTabunganPosting($siswa, 'setor', 150000, '2026-07-15');

    $kas = Akun::query()->where('kode', '1-1001')->first();
    $titipan = Akun::query()->where('kode', '2-1004')->first();

    $entries = JurnalUmum::query()
        ->where('jenis_referensi', TabunganJournalPoster::JENIS)
        ->where('referensi_id', $tabungan->id)
        ->get();

    expect($entries)->toHaveCount(2)
        ->and((string) $entries->sum('debit'))->toBe((string) $entries->sum('kredit'));

    $debitRow = $entries->firstWhere('akun_id', $kas->id);
    $kreditRow = $entries->firstWhere('akun_id', $titipan->id);

    expect((float) $debitRow->debit)->toBe(150000.0)
        ->and((float) $kreditRow->kredit)->toBe(150000.0)
        ->and($entries->first()->jenis_referensi)->toBe('tabungan_siswa');
});

// ---------------------------------------------------------------------------
// (b) Tarik (setelah setor cukup) → D Titipan / K Kas
// ---------------------------------------------------------------------------

it('(b) memposting tarik sebagai D Titipan / K Kas', function () {
    $siswa = Siswa::factory()->create();

    buatTabunganPosting($siswa, 'setor', 200000, '2026-07-15');
    $tarik = buatTabunganPosting($siswa, 'tarik', 75000, '2026-07-20');

    $kas = Akun::query()->where('kode', '1-1001')->first();
    $titipan = Akun::query()->where('kode', '2-1004')->first();

    $entries = JurnalUmum::query()
        ->where('jenis_referensi', TabunganJournalPoster::JENIS)
        ->where('referensi_id', $tarik->id)
        ->get();

    expect($entries)->toHaveCount(2);

    $titipanRow = $entries->firstWhere('akun_id', $titipan->id);
    $kasRow = $entries->firstWhere('akun_id', $kas->id);

    expect((float) $titipanRow->debit)->toBe(75000.0)
        ->and((float) $kasRow->kredit)->toBe(75000.0);
});

// ---------------------------------------------------------------------------
// (c) Setor < cut-off → TIDAK ada jurnal
// ---------------------------------------------------------------------------

it('(c) tidak memposting jurnal untuk setor sebelum cut-off', function () {
    $siswa = Siswa::factory()->create();

    $tabungan = buatTabunganPosting($siswa, 'setor', 100000, '2026-06-01');

    expect($tabungan->exists)->toBeTrue()
        ->and(
            JurnalUmum::query()
                ->where('jenis_referensi', TabunganJournalPoster::JENIS)
                ->where('referensi_id', $tabungan->id)
                ->count()
        )->toBe(0);
});

// ---------------------------------------------------------------------------
// (d) Delete setor → jurnal di-reverse (soft-delete)
// ---------------------------------------------------------------------------

it('(d) me-reverse jurnal saat baris setor dihapus', function () {
    $siswa = Siswa::factory()->create();

    $tabungan = buatTabunganPosting($siswa, 'setor', 120000, '2026-07-15');

    expect(
        JurnalUmum::query()->where('referensi_id', $tabungan->id)->count()
    )->toBe(2);

    $tabungan->delete();

    expect(
        JurnalUmum::query()->where('referensi_id', $tabungan->id)->count()
    )->toBe(0)
        ->and(
            JurnalUmum::withTrashed()->where('referensi_id', $tabungan->id)->count()
        )->toBe(2);
});

// ---------------------------------------------------------------------------
// (e) INVARIANT: saldo akun 2-1004 (dari jurnal) == SUM saldo terakhir per siswa
// ---------------------------------------------------------------------------

it('(e) menjaga invariant saldo 2-1004 == total saldo seluruh siswa', function () {
    $siswaA = Siswa::factory()->create();
    $siswaB = Siswa::factory()->create();

    // Siswa A: setor 300k, tarik 100k, setor 50k → saldo 250k
    buatTabunganPosting($siswaA, 'setor', 300000, '2026-07-01');
    buatTabunganPosting($siswaA, 'tarik', 100000, '2026-07-05');
    buatTabunganPosting($siswaA, 'setor', 50000, '2026-07-10');

    // Siswa B: setor 500k, tarik 200k → saldo 300k
    buatTabunganPosting($siswaB, 'setor', 500000, '2026-07-02');
    buatTabunganPosting($siswaB, 'tarik', 200000, '2026-07-08');

    $totalSaldoSiswa = bcadd(
        number_format(TabunganSiswa::getSaldoSiswa($siswaA->id), 2, '.', ''),
        number_format(TabunganSiswa::getSaldoSiswa($siswaB->id), 2, '.', ''),
        2
    );

    expect($totalSaldoSiswa)->toBe('550000.00')
        ->and(saldoTitipanDariJurnal())->toBe($totalSaldoSiswa);
});
