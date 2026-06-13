<?php

use App\Filament\Pages\BukuBesar;
use App\Filament\Pages\Neraca;
use App\Filament\Pages\NeracaSaldo;
use App\Filament\Pages\PerubahanModal;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Pegawai;
use App\Models\SaldoAwal;
use App\Models\SlipGaji;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\Accounting\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function ledgerOf(int $akunId, string $mulai, string $akhir): Collection
{
    $page = new BukuBesar;
    $method = new ReflectionMethod(BukuBesar::class, 'buildLedger');
    $method->setAccessible(true);

    return $method->invoke($page, [
        'akun_id' => ['value' => $akunId],
        'tanggal' => ['tanggal_mulai' => $mulai, 'tanggal_akhir' => $akhir],
    ]);
}

function bendaharaUser(string ...$pages): User
{
    foreach ($pages as $page) {
        Permission::firstOrCreate(['name' => "View:{$page}"]);
    }

    $user = User::factory()->create();
    $user->givePermissionTo(array_map(fn ($p) => "View:{$p}", $pages));

    return $user;
}

it('does not double-count saldo awal across tahun ajaran (snapshot semantics)', function () {
    $ta1 = TahunAjaran::factory()->create();
    $ta2 = TahunAjaran::factory()->create();
    $kas = Akun::factory()->aset()->create();

    // TA1 snapshot 2025-07-01 = 1.000.000 + a journal during TA1.
    SaldoAwal::create([
        'akun_id' => $kas->id,
        'tahun_ajaran_id' => $ta1->id,
        'saldo' => 1000000,
        'tanggal' => '2025-07-01',
    ]);
    JurnalUmum::factory()->debit(500000)->create(['akun_id' => $kas->id, 'tanggal' => '2025-09-01']);

    // TA2 snapshot 2026-07-01 = 1.500.000 (already carries TA1 closing 1.5jt).
    SaldoAwal::create([
        'akun_id' => $kas->id,
        'tahun_ajaran_id' => $ta2->id,
        'saldo' => 1500000,
        'tanggal' => '2026-07-01',
    ]);
    JurnalUmum::factory()->debit(250000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-07-15']);

    $saldo = app(FinancialService::class)->saldoPerAkun([$kas->id], '2026-08-01');

    // Snapshot TA2 (1.5jt) + only journal since 2026-07-01 (0.25jt) = 1.75jt.
    // NOT 1jt + 1.5jt + 0.5jt + 0.25jt (which would be double counting).
    expect($saldo[$kas->id])->toBe('1750000.00');
});

it('keeps a saldo awal dated exactly on the period start in the opening balance', function () {
    $ta = TahunAjaran::factory()->create();
    $kas = Akun::factory()->aset()->create();

    SaldoAwal::create([
        'akun_id' => $kas->id,
        'tahun_ajaran_id' => $ta->id,
        'saldo' => 1000000,
        'tanggal' => '2026-07-01',
    ]);

    JurnalUmum::factory()->debit(200000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-07-10']);

    $rows = ledgerOf($kas->id, '2026-07-01', '2026-07-31');

    // Saldo awal lands in the opening row exactly once (not lost, not doubled).
    expect($rows->first()['keterangan'])->toBe('Saldo Awal')
        ->and($rows->first()['saldo'])->toBe('1000000.00')
        ->and($rows->last()['saldo'])->toBe('1200000.00');
});

it('shows a Laba Berjalan row and stays SEIMBANG with revenue postings', function () {
    $ta = TahunAjaran::factory()->create();
    $kas = Akun::factory()->aset()->create();
    $modal = Akun::factory()->state(['tipe' => 'ekuitas', 'posisi_normal' => 'kredit'])->create();
    $pendapatan = Akun::factory()->pendapatan()->create();

    // Opening: kas 5jt = modal 5jt (balanced books).
    SaldoAwal::create(['akun_id' => $kas->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);
    SaldoAwal::create(['akun_id' => $modal->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);

    // Revenue: D Kas / K Pendapatan 1jt.
    JurnalUmum::factory()->debit(1000000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-07-10']);
    JurnalUmum::factory()->kredit(1000000)->create(['akun_id' => $pendapatan->id, 'tanggal' => '2026-07-10']);

    $user = bendaharaUser('Neraca');
    $this->actingAs($user);

    $records = collect(
        Livewire::test(Neraca::class)
            ->set('tableFilters', ['tanggal' => ['tanggal' => '2026-07-31']])
            ->instance()
            ->getTableRecords()
    );

    $labaRow = $records->firstWhere('akun', 'Laba (Rugi) Berjalan');
    expect($labaRow)->not->toBeNull()
        ->and($labaRow['saldo'])->toBe('1000000.00');

    $statusRow = $records->firstWhere('tipe', 'Seimbang');
    expect($statusRow)->not->toBeNull();
});

it('NeracaSaldo totals debit equals kredit on balanced books and gates access', function () {
    $ta = TahunAjaran::factory()->create();
    $kas = Akun::factory()->aset()->create();
    $modal = Akun::factory()->state(['tipe' => 'ekuitas', 'posisi_normal' => 'kredit'])->create();

    SaldoAwal::create(['akun_id' => $kas->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);
    SaldoAwal::create(['akun_id' => $modal->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 5000000, 'tanggal' => '2026-07-01']);

    $page = new NeracaSaldo;
    $method = new ReflectionMethod(NeracaSaldo::class, 'buildTrialBalance');
    $method->setAccessible(true);
    $rows = $method->invoke($page, ['tanggal' => ['tanggal' => '2026-07-31']]);

    $totalRow = collect($rows)->firstWhere('nama', 'TOTAL');
    expect(bccomp($totalRow['debit'], $totalRow['kredit'], 2))->toBe(0)
        ->and(collect($rows)->firstWhere('tipe', 'Seimbang'))->not->toBeNull();

    // Guru ditolak, bendahara boleh.
    Permission::firstOrCreate(['name' => 'View:NeracaSaldo']);
    $guru = User::factory()->create();
    $this->actingAs($guru);
    expect(NeracaSaldo::canAccess())->toBeFalse();

    $bendahara = bendaharaUser('NeracaSaldo');
    $this->actingAs($bendahara);
    expect(NeracaSaldo::canAccess())->toBeTrue();
});

it('chains modal akhir of one period into modal awal of the next', function () {
    $ta = TahunAjaran::factory()->create();
    $modal = Akun::factory()->state(['tipe' => 'ekuitas', 'posisi_normal' => 'kredit'])->create();
    $kas = Akun::factory()->aset()->create();
    $pendapatan = Akun::factory()->pendapatan()->create();

    SaldoAwal::create(['akun_id' => $modal->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 10000000, 'tanggal' => '2026-01-01']);
    SaldoAwal::create(['akun_id' => $kas->id, 'tahun_ajaran_id' => $ta->id, 'saldo' => 10000000, 'tanggal' => '2026-01-01']);

    // Net income in January.
    JurnalUmum::factory()->kredit(2000000)->create(['akun_id' => $pendapatan->id, 'tanggal' => '2026-01-15']);
    JurnalUmum::factory()->debit(2000000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-01-15']);

    $user = bendaharaUser('PerubahanModal');
    $this->actingAs($user);

    $jan = collect(
        Livewire::test(PerubahanModal::class)
            ->set('tableFilters', ['tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31']])
            ->instance()
            ->getTableRecords()
    );
    $feb = collect(
        Livewire::test(PerubahanModal::class)
            ->set('tableFilters', ['tanggal' => ['tanggal_mulai' => '2026-02-01', 'tanggal_akhir' => '2026-02-28']])
            ->instance()
            ->getTableRecords()
    );

    $modalAkhirJan = $jan->firstWhere('uraian', 'Modal Akhir')['nominal'];
    $modalAwalFeb = $feb->firstWhere('uraian', 'Modal Awal')['nominal'];

    expect(bccomp($modalAkhirJan, $modalAwalFeb, 2))->toBe(0);
});

it('generates unique nomor for two sequential SlipGaji creates', function () {
    $base = [
        'tahun' => 2026,
        'bulan' => 7,
        'gaji_pokok' => '5000000.00',
        'total_tunjangan' => '0.00',
        'total_potongan' => '0.00',
        'gaji_bersih' => '5000000.00',
        'nomor' => null,
    ];

    $first = SlipGaji::factory()->create(array_merge($base, ['pegawai_id' => Pegawai::factory()->create()->id]));
    $second = SlipGaji::factory()->create(array_merge($base, ['pegawai_id' => Pegawai::factory()->create()->id]));

    expect($first->nomor)->toBe('SG-202607-0001')
        ->and($second->nomor)->toBe('SG-202607-0002')
        ->and($first->nomor)->not->toBe($second->nomor);
});
