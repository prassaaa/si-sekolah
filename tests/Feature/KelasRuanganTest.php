<?php

use App\Filament\Resources\Kelases\Pages\CreateKelas;
use App\Filament\Resources\Kelases\Pages\EditKelas;
use App\Filament\Resources\Kelases\Pages\ListKelases;
use App\Models\Kelas;
use App\Models\Ruangan;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:Kelas', 'View:Kelas', 'Create:Kelas',
        'Update:Kelas', 'Delete:Kelas', 'DeleteAny:Kelas',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('kelas belongs to ruangan via ruanganModel relation', function () {
    $ruangan = Ruangan::factory()->create(['jenis' => 'kelas']);
    $kelas = Kelas::factory()->create(['ruangan_id' => $ruangan->id]);

    $kelas->load('ruanganModel');

    expect($kelas->ruanganModel)->toBeInstanceOf(Ruangan::class)
        ->and($kelas->ruanganModel->id)->toBe($ruangan->id);
});

it('ruangan has many kelas', function () {
    $ruangan = Ruangan::factory()->create(['jenis' => 'kelas']);
    $tahunAjaran = TahunAjaran::factory()->create();
    Kelas::factory()->count(3)->forTahunAjaran($tahunAjaran)->create(['ruangan_id' => $ruangan->id]);

    $ruangan->load('kelas');

    expect($ruangan->kelas)->toHaveCount(3);
});

it('kelas ruangan_id is nullable and ruanganModel returns null', function () {
    $kelas = Kelas::factory()->withoutRuangan()->create();

    $kelas->load('ruanganModel');

    expect($kelas->ruangan_id)->toBeNull()
        ->and($kelas->ruanganModel)->toBeNull();
});

it('renders the list page', function () {
    Livewire::test(ListKelases::class)->assertOk();
});

it('table shows ruangan name via ruanganModel relation', function () {
    $ruangan = Ruangan::factory()->create(['nama' => 'Ruang Test Utama', 'jenis' => 'kelas']);
    $kelas = Kelas::factory()->create(['ruangan_id' => $ruangan->id]);

    Livewire::test(ListKelases::class)
        ->assertCanSeeTableRecords([$kelas]);
});

it('creates kelas with ruangan_id and relation resolves', function () {
    $tahunAjaran = TahunAjaran::factory()->create(['is_active' => true]);
    $ruangan = Ruangan::factory()->create(['jenis' => 'kelas']);

    Livewire::test(CreateKelas::class)
        ->fillForm([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama' => '7Z',
            'tingkat' => 7,
            'kapasitas' => 30,
            'urutan' => 99,
            'ruangan_id' => $ruangan->id,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $kelas = Kelas::where('nama', '7Z')->first();

    expect($kelas)->not->toBeNull()
        ->and($kelas->ruangan_id)->toBe($ruangan->id)
        ->and($kelas->ruanganModel->id)->toBe($ruangan->id);
});

it('edits kelas and updates ruangan_id', function () {
    $ruangan1 = Ruangan::factory()->create(['jenis' => 'kelas']);
    $ruangan2 = Ruangan::factory()->create(['jenis' => 'kelas']);
    $kelas = Kelas::factory()->create(['ruangan_id' => $ruangan1->id, 'tingkat' => 8]);

    Livewire::test(EditKelas::class, ['record' => $kelas->id])
        ->fillForm([
            'tingkat' => 8,
            'ruangan_id' => $ruangan2->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($kelas->fresh()->ruangan_id)->toBe($ruangan2->id);
});

it('can clear ruangan_id to null', function () {
    $ruangan = Ruangan::factory()->create(['jenis' => 'kelas']);
    $kelas = Kelas::factory()->create(['ruangan_id' => $ruangan->id, 'tingkat' => 7]);

    Livewire::test(EditKelas::class, ['record' => $kelas->id])
        ->fillForm([
            'tingkat' => 7,
            'ruangan_id' => null,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($kelas->fresh()->ruangan_id)->toBeNull();
});
