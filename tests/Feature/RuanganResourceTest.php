<?php

use App\Filament\Resources\Ruangans\Pages\CreateRuangan;
use App\Filament\Resources\Ruangans\Pages\EditRuangan;
use App\Filament\Resources\Ruangans\Pages\ListRuangans;
use App\Models\Ruangan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:Ruangan', 'View:Ruangan', 'Create:Ruangan',
        'Update:Ruangan', 'Delete:Ruangan', 'DeleteAny:Ruangan',
        'ForceDelete:Ruangan', 'ForceDeleteAny:Ruangan',
        'Restore:Ruangan', 'RestoreAny:Ruangan',
        'Replicate:Ruangan', 'Reorder:Ruangan',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListRuangans::class)->assertOk();
});

it('lists ruangan records', function () {
    $records = Ruangan::factory()->count(3)->create();

    Livewire::test(ListRuangans::class)
        ->assertCanSeeTableRecords($records);
});

it('renders the create page', function () {
    Livewire::test(CreateRuangan::class)->assertOk();
});

it('creates a ruangan', function () {
    Livewire::test(CreateRuangan::class)
        ->fillForm([
            'kode' => 'R-101',
            'nama' => 'Ruang Kelas 1A',
            'jenis' => 'kelas',
            'kapasitas' => 30,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('ruangans', [
        'kode' => 'R-101',
        'nama' => 'Ruang Kelas 1A',
        'jenis' => 'kelas',
    ]);
});

it('rejects duplicate kode', function () {
    Ruangan::factory()->create(['kode' => 'R-101']);

    Livewire::test(CreateRuangan::class)
        ->fillForm([
            'kode' => 'R-101',
            'nama' => 'Ruang Lain',
            'jenis' => 'kantor',
        ])
        ->call('create')
        ->assertHasFormErrors(['kode']);
});

it('allows same kode when editing the same record', function () {
    $ruangan = Ruangan::factory()->create(['kode' => 'R-101']);

    Livewire::test(EditRuangan::class, ['record' => $ruangan->id])
        ->fillForm(['nama' => 'Ruang Kelas 1A Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('ruangans', [
        'id' => $ruangan->id,
        'nama' => 'Ruang Kelas 1A Updated',
    ]);
});
