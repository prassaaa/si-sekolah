<?php

use App\Filament\Pages\LaporanDebitKredit;
use App\Models\Akun;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\User;
use Database\Seeders\AkunSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;

use function Pest\Laravel\seed;

uses(RefreshDatabase::class);

/**
 * #98: totals via SQL SUM + paginasi DB (bukan agregasi in-memory & paginasi 'all').
 */
beforeEach(function () {
    seed(AkunSeeder::class);

    Permission::findOrCreate('View:LaporanDebitKredit', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo('View:LaporanDebitKredit');
    $this->actingAs($user);

    $this->kas = Akun::query()->where('kode', '1-1001')->firstOrFail();
});

function buatKasMasuk(string $tanggal, float $nominal, int $i): KasMasuk
{
    return KasMasuk::query()->create([
        'nomor_bukti' => 'KM-T-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
        'akun_id' => test()->kas->id,
        'tanggal' => $tanggal,
        'nominal' => $nominal,
        'sumber' => 'Sumber '.$i,
    ]);
}

function buatKasKeluar(string $tanggal, float $nominal, int $i): KasKeluar
{
    return KasKeluar::query()->create([
        'nomor_bukti' => 'KK-T-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
        'akun_id' => test()->kas->id,
        'tanggal' => $tanggal,
        'nominal' => $nominal,
        'penerima' => 'Penerima '.$i,
    ]);
}

it('computes summary totals via SQL aggregate matching the raw sums', function () {
    KasMasuk::withoutEvents(function () {
        buatKasMasuk('2026-07-02', 100000, 1);
        buatKasMasuk('2026-07-03', 250000, 2);
    });
    KasKeluar::withoutEvents(function () {
        buatKasKeluar('2026-07-04', 40000, 1);
    });

    $page = new LaporanDebitKredit;
    invokeHitungRingkasan($page, '2026-07-01', '2026-07-31', null);

    expect($page->summary['total_masuk'])->toBe(350000.0)
        ->and($page->summary['total_keluar'])->toBe(40000.0)
        ->and($page->summary['selisih'])->toBe(310000.0)
        ->and($page->summary['jml_masuk'])->toBe(2)
        ->and($page->summary['jml_keluar'])->toBe(1);
});

it('paginates the union at the database level and keeps totals independent of the page size', function () {
    KasMasuk::withoutEvents(function () {
        foreach (range(1, 15) as $i) {
            buatKasMasuk('2026-07-'.str_pad((string) (($i % 27) + 1), 2, '0', STR_PAD_LEFT), 10000, $i);
        }
    });
    KasKeluar::withoutEvents(function () {
        foreach (range(1, 5) as $i) {
            buatKasKeluar('2026-07-'.str_pad((string) (($i % 27) + 1), 2, '0', STR_PAD_LEFT), 5000, $i);
        }
    });

    $page = new LaporanDebitKredit;

    // Halaman 1 dengan 10 per halaman: hanya 10 baris dimuat, total tetap 20.
    $paginator = invokePaginatedRecords($page, '2026-07-01', '2026-07-31', null, 1, 10);

    expect($paginator)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($paginator->total())->toBe(20)
        ->and($paginator->items())->toHaveCount(10);

    // Halaman 2 memuat sisa 10.
    $halaman2 = invokePaginatedRecords($page, '2026-07-01', '2026-07-31', null, 2, 10);
    expect($halaman2->items())->toHaveCount(10);

    // Ringkasan SQL tidak terpengaruh paginasi.
    invokeHitungRingkasan($page, '2026-07-01', '2026-07-31', null);
    expect($page->summary['total_masuk'])->toBe(150000.0)
        ->and($page->summary['total_keluar'])->toBe(25000.0);
});

it('filters by jenis using the union query', function () {
    KasMasuk::withoutEvents(fn () => buatKasMasuk('2026-07-02', 100000, 1));
    KasKeluar::withoutEvents(fn () => buatKasKeluar('2026-07-03', 40000, 1));

    $page = new LaporanDebitKredit;

    $hanyaMasuk = invokePaginatedRecords($page, '2026-07-01', '2026-07-31', 'masuk', 1, 10);
    expect($hanyaMasuk->total())->toBe(1)
        ->and($hanyaMasuk->items()[0]['jenis'])->toBe('Kas Masuk');

    $hanyaKeluar = invokePaginatedRecords($page, '2026-07-01', '2026-07-31', 'keluar', 1, 10);
    expect($hanyaKeluar->total())->toBe(1)
        ->and($hanyaKeluar->items()[0]['jenis'])->toBe('Kas Keluar');
});

it('renders the page table without loading all rows', function () {
    KasMasuk::withoutEvents(fn () => buatKasMasuk('2026-07-02', 100000, 1));

    Livewire\Livewire::test(LaporanDebitKredit::class)
        ->assertOk();
});

/**
 * Panggil method protected paginatedRecords lewat closure terikat.
 */
function invokePaginatedRecords(LaporanDebitKredit $page, ?string $mulai, ?string $selesai, ?string $jenis, int $halaman, int $perHalaman): LengthAwarePaginator
{
    return (function () use ($mulai, $selesai, $jenis, $halaman, $perHalaman) {
        return $this->paginatedRecords($mulai, $selesai, $jenis, $halaman, $perHalaman);
    })->call($page);
}

/**
 * Panggil method protected hitungRingkasan lewat closure terikat.
 */
function invokeHitungRingkasan(LaporanDebitKredit $page, ?string $mulai, ?string $selesai, ?string $jenis): void
{
    (function () use ($mulai, $selesai, $jenis) {
        $this->hitungRingkasan($mulai, $selesai, $jenis);
    })->call($page);
}
