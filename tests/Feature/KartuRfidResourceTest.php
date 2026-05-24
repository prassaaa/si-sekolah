<?php

use App\Filament\Resources\KartuRfids\Pages\CreateKartuRfid;
use App\Filament\Resources\KartuRfids\Pages\EditKartuRfid;
use App\Filament\Resources\KartuRfids\Pages\ListKartuRfids;
use App\Models\KartuRfid;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:KartuRfid', 'View:KartuRfid', 'Create:KartuRfid',
        'Update:KartuRfid', 'Delete:KartuRfid', 'DeleteAny:KartuRfid',
        'ForceDelete:KartuRfid', 'ForceDeleteAny:KartuRfid',
        'Restore:KartuRfid', 'RestoreAny:KartuRfid',
        'Replicate:KartuRfid', 'Reorder:KartuRfid',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListKartuRfids::class)->assertOk();
});

it('lists kartu records', function () {
    $records = KartuRfid::factory()->count(3)->create();

    Livewire::test(ListKartuRfids::class)
        ->assertCanSeeTableRecords($records);
});

it('creates a kartu with normalized UID', function () {
    $siswa = Siswa::factory()->create();

    Livewire::test(CreateKartuRfid::class)
        ->fillForm([
            'owner_type' => Siswa::class,
            'owner_id' => $siswa->id,
            'uid' => '04:a1:b2:c3',
            'status' => 'aktif',
            'diaktifkan_pada' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertNotified();

    $this->assertDatabaseHas('kartu_rfids', [
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04A1B2C3',
        'status' => 'aktif',
    ]);
});

it('rejects duplicate UID', function () {
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['uid' => '04A1B2C3']);

    Livewire::test(CreateKartuRfid::class)
        ->fillForm([
            'owner_type' => Siswa::class,
            'owner_id' => $siswa->id,
            'uid' => '04A1B2C3',
            'status' => 'aktif',
            'diaktifkan_pada' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasFormErrors(['uid']);
});

it('auto-deactivates previous active card on new active card creation', function () {
    $siswa = Siswa::factory()->create();
    $kartuLama = KartuRfid::factory()->create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04A1B2C3',
        'status' => 'aktif',
    ]);

    Livewire::test(CreateKartuRfid::class)
        ->fillForm([
            'owner_type' => Siswa::class,
            'owner_id' => $siswa->id,
            'uid' => '05D4E5F6',
            'status' => 'aktif',
            'diaktifkan_pada' => now()->toDateTimeString(),
        ])
        ->call('create');

    expect($kartuLama->fresh()->status)->toBe('nonaktif');
});

it('renders the edit page and updates a kartu', function () {
    $kartu = KartuRfid::factory()->create();

    Livewire::test(EditKartuRfid::class, ['record' => $kartu->id])
        ->fillForm(['keterangan' => 'Catatan baru'])
        ->call('save')
        ->assertNotified();

    $this->assertDatabaseHas('kartu_rfids', [
        'id' => $kartu->id,
        'keterangan' => 'Catatan baru',
    ]);
});

it('filters by status', function () {
    $aktif = KartuRfid::factory()->create();
    $hilang = KartuRfid::factory()->hilang()->create();

    Livewire::test(ListKartuRfids::class)
        ->filterTable('status', ['hilang'])
        ->assertCanSeeTableRecords([$hilang])
        ->assertCanNotSeeTableRecords([$aktif]);
});
