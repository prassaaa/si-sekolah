<?php

use App\Filament\Resources\SlipGajis\Pages\CreateSlipGaji;
use App\Models\Akun;
use App\Models\JabatanPegawai;
use App\Models\JurnalUmum;
use App\Models\Pajak;
use App\Models\Pegawai;
use App\Models\SettingGaji;
use App\Models\SlipGaji;
use App\Models\User;
use App\Services\Accounting\SlipGajiJournalPoster;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Akrual selalu ber-tanggal approved_at (= now() saat approve). Agar uji posting
 * deterministik terhadap cut-off (2026-07-01), skenario "harus terjurnal"
 * dibungkus pada instant pasca cut-off ini.
 */
const PAJAK_POST_CUTOFF = '2026-07-15 09:00:00';

beforeEach(function (): void {
    $this->seed(AkunSeeder::class);
});

/**
 * Buat user pengelola payroll (punya izin Create/Update slip gaji).
 */
function buatUserPayroll(): User
{
    $permissions = [
        'ViewAny:SlipGaji', 'View:SlipGaji', 'Create:SlipGaji', 'Update:SlipGaji',
    ];

    foreach ($permissions as $p) {
        Permission::findOrCreate($p, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

/**
 * Buat pegawai (jenis jabatan tertentu) + SettingGaji aktif standar.
 *
 * SettingGaji default: gaji_pokok 5.000.000, total_tunjangan 1.150.000,
 * total_potongan 350.000, gaji_bersih (tanpa pajak) 5.800.000.
 */
function buatPegawaiPayroll(string $jenisJabatan = 'Fungsional'): Pegawai
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

    return $pegawai;
}

function akrualPajakEntries(SlipGaji $slip)
{
    return JurnalUmum::query()
        ->where('jenis_referensi', SlipGajiJournalPoster::JENIS)
        ->where('referensi_id', $slip->id)
        ->get();
}

// ---------------------------------------------------------------------------
// (a) Create slip dengan pajak 5% -> potongan_pajak benar, gaji_bersih turun,
//     manipulasi nilai dari klien diabaikan (recompute server-side).
// ---------------------------------------------------------------------------

it('(a) membuat slip dengan pajak 5%: potongan_pajak benar & gaji_bersih berkurang, manipulasi klien diabaikan', function () {
    $this->actingAs(buatUserPayroll());

    $pegawai = buatPegawaiPayroll('Fungsional');
    $pajak = Pajak::factory()->create([
        'nama' => 'PPh 21',
        'persentase' => '5.00',
        'is_active' => true,
    ]);

    // Klien mencoba menanamkan nilai turunan arbitrer; semuanya harus diabaikan.
    Livewire::test(CreateSlipGaji::class)
        ->fillForm([
            'pegawai_id' => $pegawai->id,
            'tahun' => 2026,
            'bulan' => 1,
            'status' => 'draft',
            'pajak_id' => $pajak->id,
            'gaji_pokok' => 999999999,
            'total_tunjangan' => 999999999,
            'total_potongan' => 0,
            'potongan_pajak' => 0,
            'gaji_bersih' => 999999999,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $slip = SlipGaji::first();

    // dasar = 5.000.000 + 1.150.000 = 6.150.000
    // potongan_pajak = 5% x 6.150.000 = 307.500
    // total_potongan = 350.000 + 307.500 = 657.500
    // gaji_bersih = 6.150.000 - 657.500 = 5.492.500
    expect($slip->pajak_id)->toBe($pajak->id)
        ->and((float) $slip->gaji_pokok)->toBe(5000000.0)
        ->and((float) $slip->total_tunjangan)->toBe(1150000.0)
        ->and((float) $slip->potongan_pajak)->toBe(307500.0)
        ->and((float) $slip->total_potongan)->toBe(657500.0)
        ->and((float) $slip->gaji_bersih)->toBe(5492500.0);
});

// ---------------------------------------------------------------------------
// (b) Approve slip ber-pajak -> jurnal 3 baris balanced:
//     D Beban Gaji (bruto) / K Hutang Pajak / K Hutang Gaji (net).
// ---------------------------------------------------------------------------

it('(b) menyetujui slip ber-pajak: akrual 3 baris balanced (D Beban / K Hutang Pajak / K Hutang Gaji)', function () {
    $this->travelTo(PAJAK_POST_CUTOFF);

    $pegawai = buatPegawaiPayroll('Fungsional');
    $pajak = Pajak::factory()->create(['nama' => 'PPh 21', 'persentase' => '5.00', 'is_active' => true]);

    // Slip dengan potongan pajak 307.500: net 5.492.500, bruto 5.800.000.
    $slip = SlipGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'pajak_id' => $pajak->id,
        'total_potongan' => '657500.00',
        'potongan_pajak' => '307500.00',
        'gaji_bersih' => '5492500.00',
        'status' => 'draft',
    ]);

    $slip->approve();
    $slip->refresh();

    expect($slip->status)->toBe('approved');

    $bebanGuru = Akun::query()->where('kode', '5-1001')->first();
    $hutangPajak = Akun::query()->where('kode', '2-1003')->first();
    $hutangGaji = Akun::query()->where('kode', '2-1002')->first();

    $entries = akrualPajakEntries($slip);

    expect($entries)->toHaveCount(3)
        ->and((string) $entries->sum('debit'))->toBe((string) $entries->sum('kredit'));

    $debitRow = $entries->firstWhere('akun_id', $bebanGuru->id);
    $kreditPajakRow = $entries->firstWhere('akun_id', $hutangPajak->id);
    $kreditGajiRow = $entries->firstWhere('akun_id', $hutangGaji->id);

    expect((float) $debitRow->debit)->toBe(5800000.0)
        ->and((float) $kreditPajakRow->kredit)->toBe(307500.0)
        ->and((float) $kreditGajiRow->kredit)->toBe(5492500.0);
});

it('(b) approve idempoten untuk slip ber-pajak: tetap 3 baris akrual', function () {
    $this->travelTo(PAJAK_POST_CUTOFF);

    $pegawai = buatPegawaiPayroll('Fungsional');
    $pajak = Pajak::factory()->create(['nama' => 'PPh 21', 'persentase' => '5.00', 'is_active' => true]);

    $slip = SlipGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'pajak_id' => $pajak->id,
        'total_potongan' => '657500.00',
        'potongan_pajak' => '307500.00',
        'gaji_bersih' => '5492500.00',
        'status' => 'draft',
    ]);

    $slip->approve();
    $slip->approve();
    $slip->refresh();
    app(SlipGajiJournalPoster::class)->postAkrual($slip);

    expect(akrualPajakEntries($slip))->toHaveCount(3);
});

it('(b) menghapus slip ber-pajak me-reverse seluruh 3 baris akrual', function () {
    $this->travelTo(PAJAK_POST_CUTOFF);

    $pegawai = buatPegawaiPayroll('Fungsional');
    $pajak = Pajak::factory()->create(['nama' => 'PPh 21', 'persentase' => '5.00', 'is_active' => true]);

    $slip = SlipGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'pajak_id' => $pajak->id,
        'total_potongan' => '657500.00',
        'potongan_pajak' => '307500.00',
        'gaji_bersih' => '5492500.00',
        'status' => 'draft',
    ]);

    $slip->approve();

    expect(akrualPajakEntries($slip))->toHaveCount(3);

    $slip->delete();

    expect(akrualPajakEntries($slip))->toHaveCount(0);
});

// ---------------------------------------------------------------------------
// (c) Slip tanpa pajak -> jurnal 2 baris seperti semula (perilaku tak berubah).
// ---------------------------------------------------------------------------

it('(c) menyetujui slip tanpa pajak: akrual tetap 2 baris (D Beban / K Hutang Gaji) seperti semula', function () {
    $this->travelTo(PAJAK_POST_CUTOFF);

    $pegawai = buatPegawaiPayroll('Fungsional');

    // Tanpa pajak: pajak_id null, potongan_pajak default 0.
    $slip = SlipGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'gaji_bersih' => '5800000.00',
        'status' => 'draft',
    ]);

    expect((float) $slip->potongan_pajak)->toBe(0.0);

    $slip->approve();
    $slip->refresh();

    $bebanGuru = Akun::query()->where('kode', '5-1001')->first();
    $hutangPajak = Akun::query()->where('kode', '2-1003')->first();
    $hutangGaji = Akun::query()->where('kode', '2-1002')->first();

    $entries = akrualPajakEntries($slip);

    expect($entries)->toHaveCount(2)
        ->and((string) $entries->sum('debit'))->toBe((string) $entries->sum('kredit'))
        // Tidak ada baris hutang pajak sama sekali.
        ->and($entries->firstWhere('akun_id', $hutangPajak->id))->toBeNull();

    $debitRow = $entries->firstWhere('akun_id', $bebanGuru->id);
    $kreditRow = $entries->firstWhere('akun_id', $hutangGaji->id);

    expect((float) $debitRow->debit)->toBe(5800000.0)
        ->and((float) $kreditRow->kredit)->toBe(5800000.0);
});
