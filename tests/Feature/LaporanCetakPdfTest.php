<?php

use App\Filament\Pages\LaporanGaji;
use App\Filament\Pages\LaporanPenyusutan;
use App\Models\Pegawai;
use App\Models\SarprasBarang;
use App\Models\SarprasKategori;
use App\Models\SlipGaji;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::firstOrCreate(['name' => 'View:LaporanPenyusutan']);
    Permission::firstOrCreate(['name' => 'View:LaporanGaji']);
});

function userDenganPermission(string $permission): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    return $user;
}

// ─── (c) LaporanPenyusutan: filter Per Tanggal + aksi cetak PDF ──────────────

it('(c) LaporanPenyusutan punya filter per_tanggal dan action cetakPdf yang callable', function () {
    $kategori = SarprasKategori::factory()->create(['kode' => 'ELK', 'nama' => 'Elektronik']);
    SarprasBarang::factory()->aset()->create([
        'sarpras_kategori_id' => $kategori->id,
        'harga_perolehan' => 12000000,
        'nilai_residu' => 0,
        'metode_susut' => 'garis_lurus',
        'umur_ekonomis_bulan' => 120,
        'tanggal_perolehan' => Carbon::parse('2025-01-01'),
        'is_active' => true,
    ]);

    $this->actingAs(userDenganPermission('View:LaporanPenyusutan'));

    Livewire::test(LaporanPenyusutan::class)
        ->assertOk()
        ->assertTableFilterExists('per_tanggal')
        ->assertActionExists('cetakPdf')
        ->callAction('cetakPdf')
        ->assertHasNoActionErrors()
        ->assertFileDownloaded();
});

it('(c) LaporanPenyusutan menghormati filter per_tanggal pada akumulasi', function () {
    $kategori = SarprasKategori::factory()->create(['kode' => 'ELK', 'nama' => 'Elektronik']);
    SarprasBarang::factory()->aset()->create([
        'sarpras_kategori_id' => $kategori->id,
        'harga_perolehan' => 12000000,
        'nilai_residu' => 0,
        'metode_susut' => 'garis_lurus',
        'umur_ekonomis_bulan' => 120,
        'tanggal_perolehan' => Carbon::parse('2025-01-01'),
        'is_active' => true,
    ]);

    $this->actingAs(userDenganPermission('View:LaporanPenyusutan'));

    // Per 2025-07-01 = 6 bulan penuh → akumulasi total 600.000.
    $component = Livewire::test(LaporanPenyusutan::class)
        ->set('tableFilters.per_tanggal.per_tanggal', '2025-07-01');

    $component->assertOk();

    expect($component->instance()->summary['total_akumulasi'])->toBe('Rp 600.000');
    expect($component->instance()->perTanggal)->toBe('2025-07-01');
});

// ─── (c) LaporanGaji: aksi cetak PDF ─────────────────────────────────────────

it('(c) LaporanGaji punya action cetakPdf yang callable dan mengembalikan unduhan', function () {
    $pegawai = Pegawai::factory()->create(['nama' => 'Budi Pegawai']);
    SlipGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'tahun' => now()->year,
        'bulan' => now()->month,
        'status' => 'paid',
    ]);

    $this->actingAs(userDenganPermission('View:LaporanGaji'));

    Livewire::test(LaporanGaji::class)
        ->assertOk()
        ->assertActionExists('cetakPdf')
        ->callAction('cetakPdf')
        ->assertHasNoActionErrors()
        ->assertFileDownloaded();
});
