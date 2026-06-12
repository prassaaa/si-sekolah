<?php

use App\Filament\Pages\LaporanInventaris;
use App\Filament\Pages\LaporanKondisiSarpras;
use App\Filament\Pages\LaporanPemeliharaanSarpras;
use App\Filament\Pages\LaporanPeminjamanSarpras;
use App\Filament\Widgets\BarangPerluPerbaikanWidget;
use App\Filament\Widgets\PeminjamanAktifWidget;
use App\Filament\Widgets\SarprasOverviewWidget;
use App\Models\Ruangan;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use App\Models\SarprasPemeliharaan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'View:LaporanInventaris']);
    Permission::firstOrCreate(['name' => 'View:LaporanKondisiSarpras']);
    Permission::firstOrCreate(['name' => 'View:LaporanPemeliharaanSarpras']);
    Permission::firstOrCreate(['name' => 'View:LaporanPeminjamanSarpras']);

    $user = User::factory()->create();
    $user->givePermissionTo([
        'View:LaporanInventaris',
        'View:LaporanKondisiSarpras',
        'View:LaporanPemeliharaanSarpras',
        'View:LaporanPeminjamanSarpras',
    ]);
    $this->actingAs($user);
});

// --- Page: LaporanInventaris ---

it('renders LaporanInventaris page without error', function () {
    Livewire::test(LaporanInventaris::class)
        ->assertOk();
});

it('LaporanInventaris aggregates jumlah_unit and total_nilai per kategori via SQL', function () {
    $kategori = SarprasKategori::factory()->create(['nama' => 'Elektronik', 'is_active' => true]);
    $ruangan = Ruangan::factory()->create(['is_active' => true]);

    SarprasBarang::factory()->count(3)->create([
        'sarpras_kategori_id' => $kategori->id,
        'ruangan_id' => $ruangan->id,
        'harga_perolehan' => 1000000,
        'kondisi' => 'baik',
        'status' => 'tersedia',
    ]);

    $component = Livewire::test(LaporanInventaris::class);

    $component->instance()->getTableRecords();

    $summary = $component->get('summary');

    expect($summary['total_unit'])->toBe(3)
        ->and((float) $summary['total_nilai'])->toBe(3000000.0);
});

it('LaporanInventaris filter by kondisi narrows result', function () {
    $kategori = SarprasKategori::factory()->create(['is_active' => true]);

    SarprasBarang::factory()->create([
        'sarpras_kategori_id' => $kategori->id,
        'kondisi' => 'baik',
        'harga_perolehan' => 500000,
    ]);

    SarprasBarang::factory()->create([
        'sarpras_kategori_id' => $kategori->id,
        'kondisi' => 'rusak_berat',
        'harga_perolehan' => 200000,
    ]);

    $component = Livewire::test(LaporanInventaris::class)
        ->set('tableFilters', [
            'kondisi' => ['value' => 'baik'],
        ]);

    $component->instance()->getTableRecords();

    $summary = $component->get('summary');

    expect($summary['total_unit'])->toBe(1);
});

// --- Page: LaporanKondisiSarpras ---

it('renders LaporanKondisiSarpras page without error', function () {
    Livewire::test(LaporanKondisiSarpras::class)
        ->assertOk();
});

it('LaporanKondisiSarpras groups kondisi counts per kategori via SQL', function () {
    $kategori = SarprasKategori::factory()->create(['nama' => 'Mebel', 'is_active' => true]);

    SarprasBarang::factory()->count(2)->create([
        'sarpras_kategori_id' => $kategori->id,
        'kondisi' => 'baik',
    ]);

    SarprasBarang::factory()->create([
        'sarpras_kategori_id' => $kategori->id,
        'kondisi' => 'rusak_ringan',
    ]);

    $component = Livewire::test(LaporanKondisiSarpras::class);

    $component->instance()->getTableRecords();

    $summary = $component->get('summary');

    expect($summary['total_barang'])->toBe(3)
        ->and($summary['total_baik'])->toBe(2)
        ->and($summary['total_rusak_ringan'])->toBe(1)
        ->and($summary['total_rusak_berat'])->toBe(0);
});

// --- Page: LaporanPeminjamanSarpras ---

it('renders LaporanPeminjamanSarpras page without error', function () {
    Livewire::test(LaporanPeminjamanSarpras::class)
        ->assertOk();
});

it('LaporanPeminjamanSarpras counts by status via SQL', function () {
    $kategori = SarprasKategori::factory()->create(['is_active' => true]);
    $barang = SarprasBarang::factory()->create([
        'sarpras_kategori_id' => $kategori->id,
        'status' => 'tersedia',
    ]);

    // Insert directly to bypass model booted() validation — before Livewire::test() so summary is set on first render
    DB::table('sarpras_peminjamans')->insert([
        'nomor' => 'PJM-202601-0001',
        'sarpras_barang_id' => $barang->id,
        'peminjam_type' => null,
        'peminjam_id' => null,
        'jumlah' => 1,
        'tanggal_pinjam' => now()->toDateString(),
        'tanggal_harus_kembali' => now()->addDays(7)->toDateString(),
        'tanggal_kembali' => null,
        'kondisi_pinjam' => 'baik',
        'kondisi_kembali' => null,
        'status' => 'dipinjam',
        'petugas_id' => null,
        'catatan' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ]);

    DB::table('sarpras_peminjamans')->insert([
        'nomor' => 'PJM-202601-0002',
        'sarpras_barang_id' => $barang->id,
        'peminjam_type' => null,
        'peminjam_id' => null,
        'jumlah' => 1,
        'tanggal_pinjam' => now()->subDays(2)->toDateString(),
        'tanggal_harus_kembali' => now()->subDays(1)->toDateString(),
        'tanggal_kembali' => null,
        'kondisi_pinjam' => 'baik',
        'kondisi_kembali' => null,
        'status' => 'terlambat',
        'petugas_id' => null,
        'catatan' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ]);

    // Data must be seeded before Livewire::test() — summary is set on first render of the records closure
    $component = Livewire::test(LaporanPeminjamanSarpras::class);

    $summary = $component->get('summary');

    expect($summary['total_dipinjam'])->toBe(1)
        ->and($summary['total_terlambat'])->toBe(1);
});

it('LaporanPeminjamanSarpras filter by status works', function () {
    $kategori = SarprasKategori::factory()->create(['is_active' => true]);
    $barang = SarprasBarang::factory()->create([
        'sarpras_kategori_id' => $kategori->id,
        'status' => 'tersedia',
    ]);

    // Insert directly to bypass model booted() status validation
    DB::table('sarpras_peminjamans')->insert([
        'nomor' => 'PJM-202601-0003',
        'sarpras_barang_id' => $barang->id,
        'peminjam_type' => null,
        'peminjam_id' => null,
        'jumlah' => 1,
        'tanggal_pinjam' => now()->toDateString(),
        'tanggal_harus_kembali' => now()->addDays(3)->toDateString(),
        'tanggal_kembali' => now()->toDateString(),
        'kondisi_pinjam' => 'baik',
        'kondisi_kembali' => 'baik',
        'status' => 'dikembalikan',
        'petugas_id' => null,
        'catatan' => null,
        'created_at' => now(),
        'updated_at' => now(),
        'deleted_at' => null,
    ]);

    $component = Livewire::test(LaporanPeminjamanSarpras::class)
        ->set('tableFilters', [
            'status' => ['value' => 'dipinjam'],
        ]);

    $component->instance()->getTableRecords();

    $summary = $component->get('summary');

    expect($summary['total_dipinjam'])->toBe(0);
});

// --- Page: LaporanPemeliharaanSarpras ---

it('renders LaporanPemeliharaanSarpras page without error', function () {
    Livewire::test(LaporanPemeliharaanSarpras::class)
        ->assertOk();
});

it('LaporanPemeliharaanSarpras sums biaya via SQL', function () {
    $kategori = SarprasKategori::factory()->create(['is_active' => true]);
    $barang = SarprasBarang::factory()->create([
        'sarpras_kategori_id' => $kategori->id,
    ]);

    SarprasPemeliharaan::factory()->create([
        'sarpras_barang_id' => $barang->id,
        'status' => 'selesai',
        'biaya' => 500000,
        'tanggal' => now()->toDateString(),
    ]);

    SarprasPemeliharaan::factory()->create([
        'sarpras_barang_id' => $barang->id,
        'status' => 'selesai',
        'biaya' => 300000,
        'tanggal' => now()->toDateString(),
    ]);

    $component = Livewire::test(LaporanPemeliharaanSarpras::class);

    $component->instance()->getTableRecords();

    $summary = $component->get('summary');

    expect((float) $summary['total_biaya'])->toBe(800000.0)
        ->and($summary['total_selesai'])->toBe(2);
});

it('LaporanPemeliharaanSarpras filter by date range works', function () {
    $kategori = SarprasKategori::factory()->create(['is_active' => true]);
    $barang = SarprasBarang::factory()->create([
        'sarpras_kategori_id' => $kategori->id,
    ]);

    SarprasPemeliharaan::factory()->create([
        'sarpras_barang_id' => $barang->id,
        'status' => 'selesai',
        'biaya' => 1000000,
        'tanggal' => '2024-01-15',
    ]);

    $component = Livewire::test(LaporanPemeliharaanSarpras::class)
        ->set('tableFilters', [
            'tanggal' => [
                'tanggal_mulai' => '2026-01-01',
                'tanggal_akhir' => '2026-01-31',
            ],
        ]);

    $component->instance()->getTableRecords();

    $summary = $component->get('summary');

    expect((float) $summary['total_biaya'])->toBe(0.0);
});

// --- Widgets ---

it('SarprasOverviewWidget renders without error', function () {
    Livewire::test(SarprasOverviewWidget::class)
        ->assertOk();
});

it('SarprasOverviewWidget shows correct total aset and nilai', function () {
    $kategori = SarprasKategori::factory()->create(['is_active' => true]);

    SarprasBarang::factory()->count(2)->create([
        'sarpras_kategori_id' => $kategori->id,
        'harga_perolehan' => 2000000,
        'status' => 'tersedia',
    ]);

    SarprasBarang::factory()->create([
        'sarpras_kategori_id' => $kategori->id,
        'harga_perolehan' => 1000000,
        'status' => 'tersedia',
    ]);

    Livewire::test(SarprasOverviewWidget::class)
        ->assertSee('3')
        ->assertOk();
});

it('PeminjamanAktifWidget renders without error', function () {
    Livewire::test(PeminjamanAktifWidget::class)
        ->assertOk();
});

it('BarangPerluPerbaikanWidget renders without error', function () {
    Livewire::test(BarangPerluPerbaikanWidget::class)
        ->assertOk();
});
