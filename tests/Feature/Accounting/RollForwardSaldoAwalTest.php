<?php

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SaldoAwal;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\Accounting\FinancialService;
use App\Services\Accounting\RollForwardSaldoAwalService;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

/**
 * @return array{taLama: TahunAjaran, taBaru: TahunAjaran}
 */
function rollForwardTahunAjaran(): array
{
    $taLama = TahunAjaran::create([
        'kode' => '2025/2026',
        'nama' => 'Tahun Ajaran 2025/2026',
        'tanggal_mulai' => '2025-07-01',
        'tanggal_selesai' => '2026-06-30',
        'is_active' => true,
    ]);

    $taBaru = TahunAjaran::create([
        'kode' => '2026/2027',
        'nama' => 'Tahun Ajaran 2026/2027',
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2027-06-30',
        'is_active' => false,
    ]);

    return ['taLama' => $taLama, 'taBaru' => $taBaru];
}

function rollForwardAkun(string $kode): Akun
{
    return Akun::query()->where('kode', $kode)->firstOrFail();
}

/**
 * TA lama: saldo awal Kas 10jt = Modal 10jt. Pendapatan SPP 5jt (D Kas),
 * Beban Listrik 2jt (K Kas). Laba bersih = 3jt. Saldo akhir Kas = 13jt.
 *
 * @return array{taLama: TahunAjaran, taBaru: TahunAjaran}
 */
function seedTaLama(): array
{
    ['taLama' => $taLama, 'taBaru' => $taBaru] = rollForwardTahunAjaran();

    $kas = rollForwardAkun('1-1001');
    $modal = rollForwardAkun('3-1001');
    $pendapatan = rollForwardAkun('4-1001');
    $beban = rollForwardAkun('5-2001');

    SaldoAwal::create([
        'akun_id' => $kas->id,
        'tahun_ajaran_id' => $taLama->id,
        'saldo' => 10000000,
        'tanggal' => '2025-07-01',
    ]);
    SaldoAwal::create([
        'akun_id' => $modal->id,
        'tahun_ajaran_id' => $taLama->id,
        'saldo' => 10000000,
        'tanggal' => '2025-07-01',
    ]);

    JurnalUmum::factory()->debit(5000000)->create(['akun_id' => $kas->id, 'tanggal' => '2025-09-10']);
    JurnalUmum::factory()->kredit(5000000)->create(['akun_id' => $pendapatan->id, 'tanggal' => '2025-09-10']);

    JurnalUmum::factory()->kredit(2000000)->create(['akun_id' => $kas->id, 'tanggal' => '2025-10-05']);
    JurnalUmum::factory()->debit(2000000)->create(['akun_id' => $beban->id, 'tanggal' => '2025-10-05']);

    return ['taLama' => $taLama, 'taBaru' => $taBaru];
}

it('carries real account closing balances into the new tahun ajaran saldo awal', function () {
    seed(AkunSeeder::class);
    ['taLama' => $taLama, 'taBaru' => $taBaru] = seedTaLama();

    app(RollForwardSaldoAwalService::class)->generate($taLama, $taBaru);

    $kas = rollForwardAkun('1-1001');
    $modal = rollForwardAkun('3-1001');

    $saldoKas = SaldoAwal::query()
        ->where('akun_id', $kas->id)
        ->where('tahun_ajaran_id', $taBaru->id)
        ->firstOrFail();
    $saldoModal = SaldoAwal::query()
        ->where('akun_id', $modal->id)
        ->where('tahun_ajaran_id', $taBaru->id)
        ->firstOrFail();

    expect($saldoKas->saldo)->toBe('13000000.00')
        ->and($saldoKas->tanggal->toDateString())->toBe('2026-07-01')
        ->and($saldoModal->saldo)->toBe('10000000.00');
});

it('closes net income into Laba Ditahan for the new tahun ajaran', function () {
    seed(AkunSeeder::class);
    ['taLama' => $taLama, 'taBaru' => $taBaru] = seedTaLama();

    // Laba Ditahan TA lama bersaldo 4jt sebelum penutupan.
    $labaDitahan = rollForwardAkun('3-2001');
    SaldoAwal::create([
        'akun_id' => $labaDitahan->id,
        'tahun_ajaran_id' => $taLama->id,
        'saldo' => 4000000,
        'tanggal' => '2025-07-01',
    ]);
    // Imbangi agar TA lama tetap balance: tambah modal 4jt di sisi aset (Kas).
    JurnalUmum::factory()->debit(4000000)->create(['akun_id' => rollForwardAkun('1-1001')->id, 'tanggal' => '2025-07-01']);
    JurnalUmum::factory()->kredit(4000000)->create(['akun_id' => $labaDitahan->id, 'tanggal' => '2025-07-01']);

    $ringkasan = app(RollForwardSaldoAwalService::class)->generate($taLama, $taBaru);

    $saldoLabaDitahan = SaldoAwal::query()
        ->where('akun_id', $labaDitahan->id)
        ->where('tahun_ajaran_id', $taBaru->id)
        ->firstOrFail();

    // Laba Ditahan akhir TA lama (4jt awal + 4jt jurnal = 8jt) + laba bersih 3jt = 11jt.
    expect($saldoLabaDitahan->saldo)->toBe('11000000.00')
        ->and($ringkasan['laba_ditahan_ditambah'])->toBe('3000000.00')
        ->and($ringkasan['akun_diproses'])->toBeGreaterThan(0);
});

it('does not create saldo awal for pendapatan or beban accounts', function () {
    seed(AkunSeeder::class);
    ['taLama' => $taLama, 'taBaru' => $taBaru] = seedTaLama();

    app(RollForwardSaldoAwalService::class)->generate($taLama, $taBaru);

    $nominalIds = Akun::query()->whereIn('tipe', ['pendapatan', 'beban'])->pluck('id');

    $count = SaldoAwal::query()
        ->whereIn('akun_id', $nominalIds)
        ->where('tahun_ajaran_id', $taBaru->id)
        ->count();

    expect($count)->toBe(0);
});

it('is idempotent: running twice does not duplicate saldo awal rows', function () {
    seed(AkunSeeder::class);
    ['taLama' => $taLama, 'taBaru' => $taBaru] = seedTaLama();

    $service = app(RollForwardSaldoAwalService::class);
    $service->generate($taLama, $taBaru);
    $countPertama = SaldoAwal::query()->where('tahun_ajaran_id', $taBaru->id)->count();

    $service->generate($taLama, $taBaru);
    $countKedua = SaldoAwal::query()->where('tahun_ajaran_id', $taBaru->id)->count();

    $kas = rollForwardAkun('1-1001');
    $saldoKas = SaldoAwal::query()
        ->where('akun_id', $kas->id)
        ->where('tahun_ajaran_id', $taBaru->id)
        ->firstOrFail();

    expect($countKedua)->toBe($countPertama)
        ->and($saldoKas->saldo)->toBe('13000000.00');
});

it('restores a soft-deleted saldo awal row instead of hitting the unique index', function () {
    seed(AkunSeeder::class);
    ['taLama' => $taLama, 'taBaru' => $taBaru] = seedTaLama();

    $service = app(RollForwardSaldoAwalService::class);
    $service->generate($taLama, $taBaru);

    $kas = rollForwardAkun('1-1001');
    SaldoAwal::query()
        ->where('akun_id', $kas->id)
        ->where('tahun_ajaran_id', $taBaru->id)
        ->firstOrFail()
        ->delete();

    $service->generate($taLama, $taBaru);

    $saldoKas = SaldoAwal::query()
        ->where('akun_id', $kas->id)
        ->where('tahun_ajaran_id', $taBaru->id)
        ->firstOrFail();

    expect($saldoKas->trashed())->toBeFalse()
        ->and($saldoKas->saldo)->toBe('13000000.00')
        ->and(SaldoAwal::withTrashed()->where('akun_id', $kas->id)->where('tahun_ajaran_id', $taBaru->id)->count())->toBe(1);
});

it('keeps the new tahun ajaran Neraca SEIMBANG at its tanggal mulai', function () {
    seed(AkunSeeder::class);
    ['taLama' => $taLama, 'taBaru' => $taBaru] = seedTaLama();

    app(RollForwardSaldoAwalService::class)->generate($taLama, $taBaru);

    $service = app(FinancialService::class);
    $tanggal = $taBaru->tanggal_mulai->toDateString();

    $semuaAkun = Akun::query()->get(['id', 'tipe']);
    $saldo = $service->saldoPerAkun($semuaAkun->pluck('id')->all(), $tanggal);

    $totalAset = '0.00';
    $totalKewajibanEkuitas = '0.00';

    foreach ($semuaAkun as $akun) {
        $nilai = $saldo[$akun->id] ?? '0.00';

        if ($akun->tipe === 'aset') {
            $totalAset = bcadd($totalAset, $nilai, 2);
        } elseif (in_array($akun->tipe, ['liabilitas', 'ekuitas'], true)) {
            $totalKewajibanEkuitas = bcadd($totalKewajibanEkuitas, $nilai, 2);
        }
    }

    // Tidak ada laba berjalan TA baru per tanggal mulai → Neraca harus seimbang
    // murni dari saldo awal hasil roll-forward.
    expect(bccomp($totalAset, $totalKewajibanEkuitas, 2))->toBe(0)
        ->and($totalAset)->toBe('13000000.00');
});

it('gates the generate action behind Create:SaldoAwal permission', function () {
    Permission::firstOrCreate(['name' => 'Create:SaldoAwal']);

    $tanpaIzin = User::factory()->create();
    expect($tanpaIzin->can('Create:SaldoAwal'))->toBeFalse();

    $denganIzin = User::factory()->create();
    $denganIzin->givePermissionTo('Create:SaldoAwal');
    expect($denganIzin->can('Create:SaldoAwal'))->toBeTrue();
});
