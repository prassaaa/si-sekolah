<?php

use App\Filament\Resources\SarprasKategoris\Pages\CreateSarprasKategori;
use App\Filament\Resources\SarprasKategoris\Pages\EditSarprasKategori;
use App\Filament\Resources\SarprasKategoris\Pages\ListSarprasKategoris;
use App\Models\SarprasKategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:SarprasKategori', 'View:SarprasKategori', 'Create:SarprasKategori',
        'Update:SarprasKategori', 'Delete:SarprasKategori', 'DeleteAny:SarprasKategori',
        'ForceDelete:SarprasKategori', 'ForceDeleteAny:SarprasKategori',
        'Restore:SarprasKategori', 'RestoreAny:SarprasKategori',
        'Replicate:SarprasKategori', 'Reorder:SarprasKategori',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListSarprasKategoris::class)->assertOk();
});

it('lists kategori records', function () {
    $records = SarprasKategori::factory()->count(3)->create();

    Livewire::test(ListSarprasKategoris::class)
        ->assertCanSeeTableRecords($records);
});

it('renders the create page', function () {
    Livewire::test(CreateSarprasKategori::class)->assertOk();
});

it('creates a kategori', function () {
    Livewire::test(CreateSarprasKategori::class)
        ->fillForm([
            'kode' => 'ELK',
            'nama' => 'Elektronik',
            'deskripsi' => 'Peralatan elektronik',
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('sarpras_kategoris', [
        'kode' => 'ELK',
        'nama' => 'Elektronik',
    ]);
});

it('rejects duplicate kode', function () {
    SarprasKategori::factory()->create(['kode' => 'ELK']);

    Livewire::test(CreateSarprasKategori::class)
        ->fillForm([
            'kode' => 'ELK',
            'nama' => 'Elektronik Lain',
        ])
        ->call('create')
        ->assertHasFormErrors(['kode']);
});

it('allows same kode when editing the same record', function () {
    $kategori = SarprasKategori::factory()->create(['kode' => 'ELK']);

    Livewire::test(EditSarprasKategori::class, ['record' => $kategori->id])
        ->fillForm(['nama' => 'Elektronik Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('sarpras_kategoris', [
        'id' => $kategori->id,
        'nama' => 'Elektronik Updated',
    ]);
});
