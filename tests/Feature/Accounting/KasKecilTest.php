<?php

use App\Filament\Pages\KasKecil;
use App\Models\Akun;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Beri user akses ke halaman KasKecil (pola Wave 0: permission manual via
 * Permission::findOrCreate, TIDAK menyentuh RoleSeeder).
 */
function userKasKecil(): User
{
    Permission::findOrCreate('View:KasKecil', 'web');

    $user = User::factory()->create();
    $user->givePermissionTo('View:KasKecil');
    test()->actingAs($user);

    return $user;
}

/**
 * Buat akun Kas Kecil (1-1005), akun lawan pendapatan, dan akun lawan beban.
 *
 * @return array{kas_kecil: Akun, pendapatan: Akun, beban: Akun}
 */
function akunKasKecil(): array
{
    return [
        'kas_kecil' => Akun::factory()->create([
            'kode' => '1-1005',
            'nama' => 'Kas Kecil',
            'tipe' => 'aset',
            'kategori' => 'lancar',
            'posisi_normal' => 'debit',
        ]),
        'pendapatan' => Akun::factory()->create([
            'kode' => '4-1001',
            'nama' => 'Pendapatan Operasional',
            'tipe' => 'pendapatan',
            'kategori' => 'operasional',
            'posisi_normal' => 'kredit',
        ]),
        'beban' => Akun::factory()->create([
            'kode' => '5-3003',
            'nama' => 'Beban Pemeliharaan',
            'tipe' => 'beban',
            'kategori' => 'operasional',
            'posisi_normal' => 'debit',
        ]),
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Akses dan otorisasi
// ─────────────────────────────────────────────────────────────────────────────

it('bendahara dengan izin View:KasKecil dapat mengakses halaman', function () {
    userKasKecil();
    akunKasKecil();

    Livewire::test(KasKecil::class)->assertSuccessful();
});

it('user tanpa izin View:KasKecil ditolak akses halaman', function () {
    $user = User::factory()->create();
    test()->actingAs($user);

    Livewire::test(KasKecil::class)->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Transaksi akun Kas Kecil muncul + saldo berjalan
// ─────────────────────────────────────────────────────────────────────────────

it('hanya menampilkan transaksi pada akun Kas Kecil 1-1005', function () {
    $akun = akunKasKecil();
    userKasKecil();

    $kasLain = Akun::factory()->create([
        'kode' => '1-1001',
        'nama' => 'Kas',
        'tipe' => 'aset',
        'kategori' => 'lancar',
        'posisi_normal' => 'debit',
    ]);

    // Transaksi pada Kas Kecil → muncul.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas_kecil']->id,
        'tanggal' => '2026-07-05',
        'nominal' => 500_000,
        'sumber_dana' => 'bos',
    ]);

    // Transaksi pada Kas biasa (1-1001) → TIDAK muncul.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $kasLain->id,
        'tanggal' => '2026-07-06',
        'nominal' => 9_000_000,
        'sumber_dana' => 'bos',
    ]);

    $component = Livewire::test(KasKecil::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-07'],
        ]);

    $records = collect($component->instance()->getTableRecords());

    expect($records)->toHaveCount(1)
        ->and((float) $records[0]['masuk'])->toBe(500_000.0);
});

it('saldo berjalan Kas Kecil dihitung kumulatif dengan benar', function () {
    $akun = akunKasKecil();
    userKasKecil();

    // Masuk 1.000.000 (2026-07-05) → saldo 1.000.000.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas_kecil']->id,
        'tanggal' => '2026-07-05',
        'nominal' => 1_000_000,
        'sumber_dana' => 'bos',
    ]);

    // Keluar 250.000 (2026-07-10) → saldo 750.000.
    KasKeluar::create([
        'akun_id' => $akun['beban']->id,
        'kas_akun_id' => $akun['kas_kecil']->id,
        'tanggal' => '2026-07-10',
        'nominal' => 250_000,
        'sumber_dana' => 'bos',
    ]);

    // Keluar 100.000 (2026-07-15) → saldo 650.000.
    KasKeluar::create([
        'akun_id' => $akun['beban']->id,
        'kas_akun_id' => $akun['kas_kecil']->id,
        'tanggal' => '2026-07-15',
        'nominal' => 100_000,
        'sumber_dana' => 'bos',
    ]);

    $component = Livewire::test(KasKecil::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-07'],
        ]);

    $records = collect($component->instance()->getTableRecords());

    expect($records)->toHaveCount(3)
        ->and((float) $records[0]['saldo'])->toBe(1_000_000.0)
        ->and((float) $records[1]['saldo'])->toBe(750_000.0)
        ->and((float) $records[2]['saldo'])->toBe(650_000.0);
});

it('ringkasan total masuk, keluar, dan saldo akhir benar', function () {
    $akun = akunKasKecil();
    userKasKecil();

    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas_kecil']->id,
        'tanggal' => '2026-07-01',
        'nominal' => 2_000_000,
        'sumber_dana' => 'yayasan',
    ]);
    KasKeluar::create([
        'akun_id' => $akun['beban']->id,
        'kas_akun_id' => $akun['kas_kecil']->id,
        'tanggal' => '2026-07-12',
        'nominal' => 800_000,
        'sumber_dana' => 'yayasan',
    ]);

    $component = Livewire::test(KasKecil::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-07'],
        ]);

    $component->instance()->getTableRecords();
    $ringkasan = $component->get('ringkasan');

    expect((float) $ringkasan['total_masuk'])->toBe(2_000_000.0)
        ->and((float) $ringkasan['total_keluar'])->toBe(800_000.0)
        ->and((float) $ringkasan['saldo_akhir'])->toBe(1_200_000.0);
});

it('saldo awal periode memperhitungkan transaksi bulan sebelumnya', function () {
    $akun = akunKasKecil();
    userKasKecil();

    // Masuk bulan Juni → saldo awal Juli.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas_kecil']->id,
        'tanggal' => '2026-06-20',
        'nominal' => 1_500_000,
        'sumber_dana' => 'bos',
    ]);

    // Masuk bulan Juli.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas_kecil']->id,
        'tanggal' => '2026-07-03',
        'nominal' => 500_000,
        'sumber_dana' => 'bos',
    ]);

    $component = Livewire::test(KasKecil::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-07'],
        ]);

    $component->instance()->getTableRecords();
    $ringkasan = $component->get('ringkasan');

    expect((float) $ringkasan['saldo_awal'])->toBe(1_500_000.0)
        ->and((float) $ringkasan['saldo_akhir'])->toBe(2_000_000.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Ekspor PDF
// ─────────────────────────────────────────────────────────────────────────────

it('cetakPdf tersedia dan callable di KasKecil', function () {
    akunKasKecil();
    userKasKecil();

    $component = Livewire::test(KasKecil::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-07'],
        ]);

    expect(method_exists($component->instance(), 'getHeaderActions'))->toBeTrue();

    $component->callAction('cetakPdf')->assertHasNoActionErrors();
});
