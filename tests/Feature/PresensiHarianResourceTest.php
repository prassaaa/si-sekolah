<?php

use App\Filament\Resources\PresensiHarians\Pages\CreatePresensiHarian;
use App\Filament\Resources\PresensiHarians\Pages\EditPresensiHarian;
use App\Filament\Resources\PresensiHarians\Pages\ListPresensiHarians;
use App\Filament\Resources\PresensiHarians\Pages\ViewPresensiHarian;
use App\Models\PresensiHarian;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:PresensiHarian', 'View:PresensiHarian', 'Create:PresensiHarian',
        'Update:PresensiHarian', 'Delete:PresensiHarian', 'DeleteAny:PresensiHarian',
        'ForceDelete:PresensiHarian', 'ForceDeleteAny:PresensiHarian',
        'Restore:PresensiHarian', 'RestoreAny:PresensiHarian',
        'Replicate:PresensiHarian', 'Reorder:PresensiHarian',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListPresensiHarians::class)->assertOk();
});

it('lists presensi records', function () {
    $siswa = Siswa::factory()->create();
    $records = PresensiHarian::factory()->count(3)->for($siswa)
        ->sequence(
            ['tanggal' => '2026-05-20'],
            ['tanggal' => '2026-05-21'],
            ['tanggal' => '2026-05-22'],
        )
        ->create();

    Livewire::test(ListPresensiHarians::class)
        ->assertCanSeeTableRecords($records);
});

it('creates a presensi record manually', function () {
    $siswa = Siswa::factory()->create();

    Livewire::test(CreatePresensiHarian::class)
        ->fillForm([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-05-23',
            'status' => 'hadir',
            'jam_masuk' => '07:00',
            'jam_pulang' => '13:00',
            'sumber_masuk' => 'manual',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas('presensi_harians', [
        'siswa_id' => $siswa->id,
        'status' => 'hadir',
        'sumber_masuk' => 'manual',
    ]);
});

it('auto-fills dicatat_oleh on manual create', function () {
    $siswa = Siswa::factory()->create();

    Livewire::test(CreatePresensiHarian::class)
        ->fillForm([
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-05-23',
            'status' => 'izin',
            'sumber_masuk' => 'manual',
        ])
        ->call('create');

    $record = PresensiHarian::where('siswa_id', $siswa->id)->first();
    expect($record->dicatat_oleh)->toBe(auth()->id());
});

it('validates required fields', function () {
    Livewire::test(CreatePresensiHarian::class)
        ->fillForm([
            'siswa_id' => null,
            'tanggal' => null,
            'status' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['siswa_id', 'tanggal', 'status']);
});

it('renders the view page', function () {
    $record = PresensiHarian::factory()->create();

    Livewire::test(ViewPresensiHarian::class, ['record' => $record->id])->assertOk();
});

it('renders the edit page and updates a record', function () {
    $record = PresensiHarian::factory()->hadir()->create();

    Livewire::test(EditPresensiHarian::class, ['record' => $record->id])
        ->fillForm(['status' => 'sakit', 'keterangan' => 'Demam tinggi'])
        ->call('save')
        ->assertNotified();

    $this->assertDatabaseHas('presensi_harians', [
        'id' => $record->id,
        'status' => 'sakit',
        'keterangan' => 'Demam tinggi',
    ]);
});

it('filters by status', function () {
    $hadir = PresensiHarian::factory()->hadir()->create(['tanggal' => '2026-05-20']);
    $alpha = PresensiHarian::factory()->alpha()->create(['tanggal' => '2026-05-20']);

    Livewire::test(ListPresensiHarians::class)
        ->filterTable('status', ['alpha'])
        ->assertCanSeeTableRecords([$alpha])
        ->assertCanNotSeeTableRecords([$hadir]);
});

it('rejects duplicate siswa+tanggal entries via unique constraint', function () {
    $siswa = Siswa::factory()->create();
    $tanggal = '2026-05-23';

    PresensiHarian::factory()->for($siswa)->create(['tanggal' => $tanggal]);

    Livewire::test(CreatePresensiHarian::class)
        ->fillForm([
            'siswa_id' => $siswa->id,
            'tanggal' => $tanggal,
            'status' => 'hadir',
            'sumber_masuk' => 'manual',
        ])
        ->call('create')
        ->assertHasErrors();

    expect(PresensiHarian::where('siswa_id', $siswa->id)->count())->toBe(1);
});
