<?php

use App\Filament\Pages\BukuKasUmum;
use App\Models\Akun;
use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

/**
 * Beri user akses ke BukuKasUmum (pola Wave 0: permission manual via
 * Permission::firstOrCreate, TIDAK menyentuh RoleSeeder).
 */
function userBendaharaBku(): User
{
    Permission::firstOrCreate(['name' => 'View:BukuKasUmum']);

    $user = User::factory()->create();
    $user->givePermissionTo('View:BukuKasUmum');
    test()->actingAs($user);

    return $user;
}

/**
 * Buat akun kas tunai (1-1001) + akun pendapatan + akun beban untuk uji BKU.
 *
 * @return array{kas: Akun, pendapatan: Akun, beban: Akun}
 */
function akunBku(): array
{
    return [
        'kas' => Akun::factory()->create([
            'kode' => '1-1001',
            'nama' => 'Kas',
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
            'kode' => '5-1001',
            'nama' => 'Beban Operasional',
            'tipe' => 'beban',
            'kategori' => 'operasional',
            'posisi_normal' => 'debit',
        ]),
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// Akses dan otorisasi
// ─────────────────────────────────────────────────────────────────────────────

it('bendahara dengan izin View:BukuKasUmum dapat mengakses halaman', function () {
    userBendaharaBku();
    akunBku();

    Livewire::test(BukuKasUmum::class)->assertSuccessful();
});

it('user tanpa izin View:BukuKasUmum ditolak akses halaman', function () {
    $user = User::factory()->create();
    test()->actingAs($user);

    Livewire::test(BukuKasUmum::class)->assertForbidden();
});

// ─────────────────────────────────────────────────────────────────────────────
// Saldo berjalan
// ─────────────────────────────────────────────────────────────────────────────

it('saldo berjalan dihitung kumulatif dengan benar', function () {
    $akun = akunBku();
    userBendaharaBku();

    // Penerimaan 1.000.000 pada 2026-06-05 → saldo = 1.000.000.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-05',
        'nominal' => 1_000_000,
        'sumber_dana' => 'bos',
    ]);

    // Pengeluaran 300.000 pada 2026-06-10 → saldo = 700.000.
    KasKeluar::create([
        'akun_id' => $akun['beban']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-10',
        'nominal' => 300_000,
        'sumber_dana' => 'bos',
    ]);

    // Penerimaan 500.000 pada 2026-06-15 → saldo = 1.200.000.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-15',
        'nominal' => 500_000,
        'sumber_dana' => 'bos',
    ]);

    $component = Livewire::test(BukuKasUmum::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-06'],
        ]);

    $records = collect($component->instance()->getTableRecords());

    expect($records)->toHaveCount(3);

    // Baris urut tanggal: masuk 1jt → keluar 300rb → masuk 500rb.
    expect((float) $records[0]['saldo'])->toBe(1_000_000.0)
        ->and((float) $records[1]['saldo'])->toBe(700_000.0)
        ->and((float) $records[2]['saldo'])->toBe(1_200_000.0);
});

it('saldo berjalan memperhitungkan saldo awal periode dari transaksi sebelumnya', function () {
    $akun = akunBku();
    userBendaharaBku();

    // Penerimaan SEBELUM periode → membentuk saldo awal bulan Juni.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-05-20',
        'nominal' => 2_000_000,
        'sumber_dana' => 'lainnya',
    ]);

    // Penerimaan dalam periode Juni.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-05',
        'nominal' => 500_000,
        'sumber_dana' => 'lainnya',
    ]);

    $component = Livewire::test(BukuKasUmum::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-06'],
        ]);

    $component->instance()->getTableRecords();
    $ringkasan = $component->get('ringkasan');

    // Saldo awal = 2.000.000 (dari Mei), + 500.000 = 2.500.000.
    expect((float) $ringkasan['saldo_awal'])->toBe(2_000_000.0)
        ->and((float) $ringkasan['saldo_akhir'])->toBe(2_500_000.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Filter sumber_dana
// ─────────────────────────────────────────────────────────────────────────────

it('filter sumber_dana BOS hanya menampilkan transaksi BOS', function () {
    $akun = akunBku();
    userBendaharaBku();

    // Dua penerimaan BOS.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-05',
        'nominal' => 1_000_000,
        'sumber_dana' => 'bos',
    ]);
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-10',
        'nominal' => 750_000,
        'sumber_dana' => 'bos',
    ]);

    // Satu penerimaan komite — tidak boleh muncul di filter BOS.
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-12',
        'nominal' => 500_000,
        'sumber_dana' => 'komite',
    ]);

    // Satu pengeluaran komite — tidak boleh muncul di filter BOS.
    KasKeluar::create([
        'akun_id' => $akun['beban']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-15',
        'nominal' => 200_000,
        'sumber_dana' => 'komite',
    ]);

    $component = Livewire::test(BukuKasUmum::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-06'],
            'sumber_dana' => ['value' => 'bos'],
        ]);

    $records = collect($component->instance()->getTableRecords());

    // Hanya 2 transaksi BOS yang tampil.
    expect($records)->toHaveCount(2)
        ->and($records->every(fn ($r) => $r['sumber_dana'] === 'bos'))->toBeTrue();
});

// ─────────────────────────────────────────────────────────────────────────────
// Total penerimaan dan pengeluaran
// ─────────────────────────────────────────────────────────────────────────────

it('total penerimaan dan pengeluaran dihitung dengan benar', function () {
    $akun = akunBku();
    userBendaharaBku();

    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-01',
        'nominal' => 3_000_000,
        'sumber_dana' => 'yayasan',
    ]);
    KasMasuk::create([
        'akun_id' => $akun['pendapatan']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-10',
        'nominal' => 2_000_000,
        'sumber_dana' => 'yayasan',
    ]);
    KasKeluar::create([
        'akun_id' => $akun['beban']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-15',
        'nominal' => 1_500_000,
        'sumber_dana' => 'yayasan',
    ]);
    KasKeluar::create([
        'akun_id' => $akun['beban']->id,
        'kas_akun_id' => $akun['kas']->id,
        'tanggal' => '2026-06-20',
        'nominal' => 500_000,
        'sumber_dana' => 'yayasan',
    ]);

    $component = Livewire::test(BukuKasUmum::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-06'],
        ]);

    $component->instance()->getTableRecords();
    $ringkasan = $component->get('ringkasan');

    expect((float) $ringkasan['total_penerimaan'])->toBe(5_000_000.0)
        ->and((float) $ringkasan['total_pengeluaran'])->toBe(2_000_000.0)
        ->and((float) $ringkasan['saldo_akhir'])->toBe(3_000_000.0);
});

// ─────────────────────────────────────────────────────────────────────────────
// Ekspor PDF
// ─────────────────────────────────────────────────────────────────────────────

it('cetakPdf tersedia dan callable di BukuKasUmum', function () {
    akunBku();
    userBendaharaBku();

    $component = Livewire::test(BukuKasUmum::class)
        ->set('tableFilters', [
            'periode' => ['bulan' => '2026-06'],
        ]);

    expect(method_exists($component->instance(), 'getHeaderActions'))->toBeTrue();

    $component->callAction('cetakPdf')->assertHasNoActionErrors();
});
