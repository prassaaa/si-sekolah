<?php

use App\Filament\Resources\PresensiHarianPegawais\Pages\CreatePresensiHarianPegawai;
use App\Filament\Resources\PresensiHarianPegawais\Pages\EditPresensiHarianPegawai;
use App\Filament\Resources\PresensiHarianPegawais\Pages\ListPresensiHarianPegawais;
use App\Models\Pegawai;
use App\Models\PresensiHarianPegawai;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:PresensiHarianPegawai', 'View:PresensiHarianPegawai', 'Create:PresensiHarianPegawai',
        'Update:PresensiHarianPegawai', 'Delete:PresensiHarianPegawai', 'DeleteAny:PresensiHarianPegawai',
        'ForceDelete:PresensiHarianPegawai', 'ForceDeleteAny:PresensiHarianPegawai',
        'Restore:PresensiHarianPegawai', 'RestoreAny:PresensiHarianPegawai',
        'Replicate:PresensiHarianPegawai', 'Reorder:PresensiHarianPegawai',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('renders the list page', function () {
    Livewire::test(ListPresensiHarianPegawais::class)->assertOk();
});

it('lists presensi pegawai records', function () {
    $pegawai = Pegawai::factory()->create();
    $records = PresensiHarianPegawai::factory()->count(3)->for($pegawai)
        ->sequence(
            ['tanggal' => '2026-05-20'],
            ['tanggal' => '2026-05-21'],
            ['tanggal' => '2026-05-22'],
        )
        ->create();

    Livewire::test(ListPresensiHarianPegawais::class)
        ->assertCanSeeTableRecords($records);
});

it('creates a presensi pegawai record manually', function () {
    $pegawai = Pegawai::factory()->create();

    Livewire::test(CreatePresensiHarianPegawai::class)
        ->fillForm([
            'pegawai_id' => $pegawai->id,
            'tanggal' => '2026-05-23',
            'status' => 'hadir',
            'jam_masuk' => '07:00',
            'jam_pulang' => '15:00',
            'sumber_masuk' => 'manual',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas('presensi_harian_pegawais', [
        'pegawai_id' => $pegawai->id,
        'status' => 'hadir',
        'sumber_masuk' => 'manual',
    ]);
});

it('supports cuti and dinas_luar status (pegawai-specific)', function () {
    $pegawai = Pegawai::factory()->create();

    Livewire::test(CreatePresensiHarianPegawai::class)
        ->fillForm([
            'pegawai_id' => $pegawai->id,
            'tanggal' => '2026-05-23',
            'status' => 'cuti',
            'sumber_masuk' => 'manual',
        ])
        ->call('create')
        ->assertNotified();

    $this->assertDatabaseHas('presensi_harian_pegawais', [
        'pegawai_id' => $pegawai->id,
        'status' => 'cuti',
    ]);
});

it('validates required fields', function () {
    Livewire::test(CreatePresensiHarianPegawai::class)
        ->fillForm([
            'pegawai_id' => null,
            'tanggal' => null,
            'status' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['pegawai_id', 'tanggal', 'status']);
});

it('rejects duplicate pegawai+tanggal entries', function () {
    $pegawai = Pegawai::factory()->create();
    $tanggal = '2026-05-23';

    PresensiHarianPegawai::factory()->for($pegawai)->create(['tanggal' => $tanggal]);

    Livewire::test(CreatePresensiHarianPegawai::class)
        ->fillForm([
            'pegawai_id' => $pegawai->id,
            'tanggal' => $tanggal,
            'status' => 'hadir',
            'sumber_masuk' => 'manual',
        ])
        ->call('create')
        ->assertHasErrors();

    expect(PresensiHarianPegawai::where('pegawai_id', $pegawai->id)->count())->toBe(1);
});

it('renders edit page and updates a record', function () {
    $record = PresensiHarianPegawai::factory()->hadir()->create();

    Livewire::test(EditPresensiHarianPegawai::class, ['record' => $record->id])
        ->fillForm(['status' => 'sakit', 'keterangan' => 'Demam'])
        ->call('save')
        ->assertNotified();

    $this->assertDatabaseHas('presensi_harian_pegawais', [
        'id' => $record->id,
        'status' => 'sakit',
        'keterangan' => 'Demam',
    ]);
});

it('filters by cuti status', function () {
    $hadir = PresensiHarianPegawai::factory()->hadir()->create();
    $cuti = PresensiHarianPegawai::factory()->cuti()->create();

    Livewire::test(ListPresensiHarianPegawais::class)
        ->filterTable('status', ['cuti'])
        ->assertCanSeeTableRecords([$cuti])
        ->assertCanNotSeeTableRecords([$hadir]);
});
