<?php

use App\Models\Akun;
use App\Models\JabatanPegawai;
use App\Models\JurnalUmum;
use App\Models\KasKeluar;
use App\Models\Pegawai;
use App\Models\SettingGaji;
use App\Models\SlipGaji;
use App\Services\Accounting\KasJournalPoster;
use App\Services\Accounting\SlipGajiJournalPoster;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Akrual selalu ber-tanggal approved_at (= now() saat approve). Agar uji
 * posting deterministik terhadap cut-off (2026-07-01) tanpa bergantung pada
 * tanggal kalender saat test berjalan, semua skenario "harus terjurnal"
 * dibungkus pada instant pasca cut-off ini.
 */
const POST_CUTOFF = '2026-07-15 09:00:00';

const PRE_CUTOFF = '2026-06-01 09:00:00';

beforeEach(function (): void {
    $this->seed(AkunSeeder::class);
});

/**
 * Buat slip gaji draft untuk pegawai dengan jenis jabatan tertentu.
 */
function buatSlipUntukJenisJabatan(string $jenisJabatan, float $gajiBersih = 5000000): SlipGaji
{
    $jabatan = JabatanPegawai::factory()->create(['jenis' => $jenisJabatan]);
    $pegawai = Pegawai::factory()->create([
        'user_id' => null,
        'jabatan_id' => $jabatan->id,
        'nama' => 'Pegawai '.$jenisJabatan,
    ]);
    SettingGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'is_active' => true,
    ]);

    return SlipGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'gaji_bersih' => number_format($gajiBersih, 2, '.', ''),
        'status' => 'draft',
    ]);
}

function akrualEntries(SlipGaji $slip)
{
    return JurnalUmum::query()
        ->where('jenis_referensi', SlipGajiJournalPoster::JENIS)
        ->where('referensi_id', $slip->id)
        ->get();
}

// ---------------------------------------------------------------------------
// (a) Approve slip guru -> approved + akrual D 5-1001 / K 2-1002
// ---------------------------------------------------------------------------

it('(a) menyetujui slip guru: status approved + akrual D Beban Gaji Guru / K Hutang Gaji', function () {
    $this->travelTo(POST_CUTOFF);

    $slip = buatSlipUntukJenisJabatan('Fungsional', 4200000);
    $slip->approve();
    $slip->refresh();

    expect($slip->status)->toBe('approved')
        ->and($slip->approved_at)->not->toBeNull();

    $bebanGuru = Akun::query()->where('kode', '5-1001')->first();
    $hutangGaji = Akun::query()->where('kode', '2-1002')->first();

    $entries = akrualEntries($slip);

    expect($entries)->toHaveCount(2)
        ->and((string) $entries->sum('debit'))->toBe((string) $entries->sum('kredit'));

    $debitRow = $entries->firstWhere('akun_id', $bebanGuru->id);
    $kreditRow = $entries->firstWhere('akun_id', $hutangGaji->id);

    expect((float) $debitRow->debit)->toBe(4200000.0)
        ->and((float) $kreditRow->kredit)->toBe(4200000.0);
});

// ---------------------------------------------------------------------------
// (b) Approve slip karyawan -> akrual debit ke 5-1002
// ---------------------------------------------------------------------------

it('(b) menyetujui slip karyawan: akrual debit ke Beban Gaji Karyawan (5-1002)', function () {
    $this->travelTo(POST_CUTOFF);

    $slip = buatSlipUntukJenisJabatan('Struktural', 3000000);
    $slip->approve();

    $bebanKaryawan = Akun::query()->where('kode', '5-1002')->first();
    $bebanGuru = Akun::query()->where('kode', '5-1001')->first();

    $entries = akrualEntries($slip);
    $debitRow = $entries->firstWhere('debit', '>', 0);

    expect($entries)->toHaveCount(2)
        ->and($debitRow->akun_id)->toBe($bebanKaryawan->id)
        ->and($entries->firstWhere('akun_id', $bebanGuru->id))->toBeNull()
        ->and((float) $debitRow->debit)->toBe(3000000.0);
});

// ---------------------------------------------------------------------------
// (c) Bayar slip approved -> KasKeluar D 2-1002 / K 1-1001 + status paid
// ---------------------------------------------------------------------------

it('(c) membayar slip approved: membuat KasKeluar (D Hutang Gaji / K Kas), status paid', function () {
    $this->travelTo(POST_CUTOFF);

    $slip = buatSlipUntukJenisJabatan('Fungsional', 5000000);
    $slip->approve();
    $slip->bayar();
    $slip->refresh();

    expect($slip->status)->toBe('paid')
        ->and($slip->kas_keluar_id)->not->toBeNull()
        ->and($slip->paid_at)->not->toBeNull()
        ->and($slip->tanggal_bayar)->not->toBeNull();

    $kasKeluar = KasKeluar::find($slip->kas_keluar_id);
    $hutangGaji = Akun::query()->where('kode', '2-1002')->first();
    $kas = Akun::query()->where('kode', '1-1001')->first();

    expect($kasKeluar)->not->toBeNull()
        ->and($kasKeluar->akun_id)->toBe($hutangGaji->id)
        ->and($kasKeluar->kas_akun_id)->toBe($kas->id)
        ->and((float) $kasKeluar->nominal)->toBe(5000000.0)
        ->and($kasKeluar->penerima)->toBe('Pegawai Fungsional');

    // Jurnal kas keluar dibuat otomatis oleh KasKeluarObserver: D Hutang Gaji / K Kas.
    $kkEntries = JurnalUmum::query()
        ->where('jenis_referensi', KasJournalPoster::JENIS_KAS_KELUAR)
        ->where('referensi_id', $kasKeluar->id)
        ->get();

    expect($kkEntries)->toHaveCount(2);

    $debitRow = $kkEntries->firstWhere('akun_id', $hutangGaji->id);
    $kreditRow = $kkEntries->firstWhere('akun_id', $kas->id);

    expect((float) $debitRow->debit)->toBe(5000000.0)
        ->and((float) $kreditRow->kredit)->toBe(5000000.0);
});

// ---------------------------------------------------------------------------
// (d) Approve dua kali idempoten -> tetap 1 pasang akrual
// ---------------------------------------------------------------------------

it('(d) approve idempoten: memanggil approve dua kali tetap menghasilkan satu pasang akrual', function () {
    $this->travelTo(POST_CUTOFF);

    $slip = buatSlipUntukJenisJabatan('Fungsional', 4000000);
    $slip->approve();
    $slip->approve();      // guard isDraft() -> no-op
    $slip->refresh();

    // Repost langsung lewat poster juga harus idempoten (sudah approved).
    app(SlipGajiJournalPoster::class)->postAkrual($slip);

    expect($slip->status)->toBe('approved')
        ->and(akrualEntries($slip))->toHaveCount(2);
});

// ---------------------------------------------------------------------------
// (e) Bayar dua kali idempoten -> tetap 1 KasKeluar
// ---------------------------------------------------------------------------

it('(e) bayar idempoten: memanggil bayar dua kali tetap menghasilkan satu KasKeluar', function () {
    $this->travelTo(POST_CUTOFF);

    $slip = buatSlipUntukJenisJabatan('Struktural', 3500000);
    $slip->approve();
    $slip->bayar();
    $firstKasKeluarId = $slip->fresh()->kas_keluar_id;

    $slip->bayar();        // guard isApproved()/kas_keluar_id -> no-op
    $slip->refresh();

    expect($slip->kas_keluar_id)->toBe($firstKasKeluarId)
        ->and(KasKeluar::query()->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// (f) Akrual sebelum cut-off -> tidak terjurnal
// ---------------------------------------------------------------------------

it('(f) approval sebelum cut-off tidak menjurnal akrual (status tetap approved)', function () {
    // approve() men-set approved_at = now(); pada instant pra cut-off, gate
    // cut-off poster menolak posting walaupun slip sudah approved.
    $this->travelTo(PRE_CUTOFF);

    $slip = buatSlipUntukJenisJabatan('Fungsional', 4800000);
    $slip->approve();
    $slip->refresh();

    expect($slip->status)->toBe('approved')
        ->and($slip->approved_at)->not->toBeNull()
        ->and(akrualEntries($slip))->toHaveCount(0);

    // Pembuktian eksplisit gate cut-off pada poster: set approved_at lampau,
    // postAkrual tetap tidak menjurnal.
    $slip->approved_at = PRE_CUTOFF;
    $slip->save();
    app(SlipGajiJournalPoster::class)->postAkrual($slip);

    expect(akrualEntries($slip))->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// (g) Delete slip -> akrual (dan KasKeluar) ter-reverse
// ---------------------------------------------------------------------------

it('(g) menghapus slip approved me-reverse akrual', function () {
    $this->travelTo(POST_CUTOFF);

    $slip = buatSlipUntukJenisJabatan('Fungsional', 4000000);
    $slip->approve();

    expect(akrualEntries($slip))->toHaveCount(2);

    $slip->delete();

    expect(akrualEntries($slip))->toHaveCount(0)
        ->and(JurnalUmum::withTrashed()->where('jenis_referensi', SlipGajiJournalPoster::JENIS)->where('referensi_id', $slip->id)->count())->toBe(2);
});

it('(g) menghapus slip paid me-reverse akrual + KasKeluar beserta jurnalnya', function () {
    $this->travelTo(POST_CUTOFF);

    $slip = buatSlipUntukJenisJabatan('Struktural', 3000000);
    $slip->approve();
    $slip->bayar();
    $slip->refresh();

    $kasKeluarId = $slip->kas_keluar_id;

    expect(akrualEntries($slip))->toHaveCount(2)
        ->and(JurnalUmum::query()->where('jenis_referensi', KasJournalPoster::JENIS_KAS_KELUAR)->where('referensi_id', $kasKeluarId)->count())->toBe(2);

    $slip->delete();

    // Akrual ter-reverse.
    expect(akrualEntries($slip))->toHaveCount(0)
        // KasKeluar ter-soft-delete...
        ->and(KasKeluar::query()->find($kasKeluarId))->toBeNull()
        ->and(KasKeluar::withTrashed()->find($kasKeluarId))->not->toBeNull()
        // ...dan jurnal kas keluarnya ikut ter-reverse oleh KasKeluarObserver.
        ->and(JurnalUmum::query()->where('jenis_referensi', KasJournalPoster::JENIS_KAS_KELUAR)->where('referensi_id', $kasKeluarId)->count())->toBe(0);
});
