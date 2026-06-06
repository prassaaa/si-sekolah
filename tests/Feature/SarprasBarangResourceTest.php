<?php

use App\Filament\Resources\SarprasBarangs\Pages\CreateSarprasBarang;
use App\Filament\Resources\SarprasBarangs\Pages\EditSarprasBarang;
use App\Filament\Resources\SarprasBarangs\Pages\ListSarprasBarangs;
use App\Filament\Resources\SarprasBarangs\Pages\ViewSarprasBarang;
use App\Models\SarprasBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:SarprasBarang', 'View:SarprasBarang', 'Create:SarprasBarang',
        'Update:SarprasBarang', 'Delete:SarprasBarang', 'DeleteAny:SarprasBarang',
        'ForceDelete:SarprasBarang', 'ForceDeleteAny:SarprasBarang',
        'Restore:SarprasBarang', 'RestoreAny:SarprasBarang',
        'Replicate:SarprasBarang', 'Reorder:SarprasBarang',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListSarprasBarangs::class)->assertOk();
});

it('renders the create page', function () {
    Livewire::test(CreateSarprasBarang::class)->assertOk();
});

it('renders the edit page', function () {
    $barang = SarprasBarang::factory()->create();

    Livewire::test(EditSarprasBarang::class, ['record' => $barang->id])
        ->assertOk();
});

it('renders the view page', function () {
    $barang = SarprasBarang::factory()->create();

    Livewire::test(ViewSarprasBarang::class, ['record' => $barang->id])
        ->assertOk();
});

it('creates a barang and persists to database', function () {
    $barang = SarprasBarang::factory()->make();

    Livewire::test(CreateSarprasBarang::class)
        ->fillForm([
            'kode_inventaris' => 'INV-TEST-001',
            'nama' => 'Laptop Dell Test',
            'sarpras_kategori_id' => $barang->sarpras_kategori_id,
            'ruangan_id' => $barang->ruangan_id,
            'tipe' => 'aset',
            'kondisi' => 'baik',
            'status' => 'tersedia',
            'sumber_dana' => 'bos',
            'harga_perolehan' => 1000000,
            'jumlah' => 1,
            'satuan' => 'unit',
            'is_active' => true,
        ])
        ->call('create')
        ->assertNotified();

    $this->assertDatabaseHas('sarpras_barangs', [
        'kode_inventaris' => 'INV-TEST-001',
        'nama' => 'Laptop Dell Test',
        'tipe' => 'aset',
        'kondisi' => 'baik',
        'status' => 'tersedia',
    ]);
});

it('rejects duplicate kode_inventaris', function () {
    SarprasBarang::factory()->create(['kode_inventaris' => 'INV-DUPLIKAT-001']);

    $barang = SarprasBarang::factory()->make();

    Livewire::test(CreateSarprasBarang::class)
        ->fillForm([
            'kode_inventaris' => 'INV-DUPLIKAT-001',
            'nama' => 'Barang Lain',
            'sarpras_kategori_id' => $barang->sarpras_kategori_id,
            'tipe' => 'aset',
            'kondisi' => 'baik',
            'jumlah' => 1,
        ])
        ->call('create')
        ->assertHasFormErrors(['kode_inventaris']);
});

it('filters by kondisi', function () {
    $baik = SarprasBarang::factory()->create(['kondisi' => 'baik']);
    $rusak = SarprasBarang::factory()->create(['kondisi' => 'rusak_berat']);

    Livewire::test(ListSarprasBarangs::class)
        ->filterTable('kondisi', 'rusak_berat')
        ->assertCanSeeTableRecords([$rusak])
        ->assertCanNotSeeTableRecords([$baik]);
});
