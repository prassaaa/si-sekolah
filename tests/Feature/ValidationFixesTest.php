<?php

use App\Filament\Resources\JurnalUmums\Pages\CreateJurnalUmum;
use App\Filament\Resources\Kelases\Pages\CreateKelas;
use App\Filament\Resources\Semesters\Pages\CreateSemester;
use App\Filament\Resources\SettingGajis\Pages\CreateSettingGaji;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\Kelas;
use App\Models\Pegawai;
use App\Models\Semester;
use App\Models\SettingGaji;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function setupUserWithPermissions(array $permissions): User
{
    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

// ── Kelas: duplicate nama scoped to tahun_ajaran_id ──────────────────────────

it('rejects duplicate kelas nama within the same tahun ajaran', function () {
    $user = setupUserWithPermissions([
        'ViewAny:Kelas', 'View:Kelas', 'Create:Kelas', 'Update:Kelas',
        'Delete:Kelas', 'DeleteAny:Kelas', 'ForceDelete:Kelas',
        'ForceDeleteAny:Kelas', 'Restore:Kelas', 'RestoreAny:Kelas',
        'Replicate:Kelas', 'Reorder:Kelas',
    ]);
    $this->actingAs($user);

    $tahunAjaran = TahunAjaran::factory()->create();
    Kelas::factory()->create(['tahun_ajaran_id' => $tahunAjaran->id, 'nama' => '7A']);

    Livewire::test(CreateKelas::class)
        ->fillForm([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'nama' => '7A',
            'tingkat' => 7,
            'kapasitas' => 30,
        ])
        ->call('create')
        ->assertHasFormErrors(['nama']);
});

it('allows same kelas nama in different tahun ajaran', function () {
    $user = setupUserWithPermissions([
        'ViewAny:Kelas', 'View:Kelas', 'Create:Kelas', 'Update:Kelas',
        'Delete:Kelas', 'DeleteAny:Kelas', 'ForceDelete:Kelas',
        'ForceDeleteAny:Kelas', 'Restore:Kelas', 'RestoreAny:Kelas',
        'Replicate:Kelas', 'Reorder:Kelas',
    ]);
    $this->actingAs($user);

    $tahunAjaran1 = TahunAjaran::factory()->create();
    $tahunAjaran2 = TahunAjaran::factory()->create();
    Kelas::factory()->create(['tahun_ajaran_id' => $tahunAjaran1->id, 'nama' => '7A']);

    Livewire::test(CreateKelas::class)
        ->fillForm([
            'tahun_ajaran_id' => $tahunAjaran2->id,
            'nama' => '7A',
            'tingkat' => 7,
            'kapasitas' => 30,
        ])
        ->call('create')
        ->assertHasNoFormErrors(['nama']);
});

// ── Semester: composite unique (tahun_ajaran_id, semester) ───────────────────

it('rejects duplicate semester value within the same tahun ajaran', function () {
    $user = setupUserWithPermissions([
        'ViewAny:Semester', 'View:Semester', 'Create:Semester', 'Update:Semester',
        'Delete:Semester', 'DeleteAny:Semester', 'ForceDelete:Semester',
        'ForceDeleteAny:Semester', 'Restore:Semester', 'RestoreAny:Semester',
        'Replicate:Semester', 'Reorder:Semester',
    ]);
    $this->actingAs($user);

    $tahunAjaran = TahunAjaran::factory()->create();
    Semester::factory()->create([
        'tahun_ajaran_id' => $tahunAjaran->id,
        'semester' => 1,
    ]);

    Livewire::test(CreateSemester::class)
        ->fillForm([
            'tahun_ajaran_id' => $tahunAjaran->id,
            'semester' => 1,
            'nama' => 'Semester Ganjil Duplikat',
            'tanggal_mulai' => '2025-07-01',
            'tanggal_selesai' => '2025-12-31',
        ])
        ->call('create')
        ->assertHasFormErrors(['semester']);
});

// ── SettingGaji: minValue on gaji_pokok (must be >= 1) ───────────────────────

it('rejects gaji_pokok of zero', function () {
    $user = setupUserWithPermissions([
        'ViewAny:SettingGaji', 'View:SettingGaji', 'Create:SettingGaji', 'Update:SettingGaji',
        'Delete:SettingGaji', 'DeleteAny:SettingGaji', 'ForceDelete:SettingGaji',
        'ForceDeleteAny:SettingGaji', 'Restore:SettingGaji', 'RestoreAny:SettingGaji',
        'Replicate:SettingGaji', 'Reorder:SettingGaji',
    ]);
    $this->actingAs($user);

    $pegawai = Pegawai::factory()->create();

    Livewire::test(CreateSettingGaji::class)
        ->fillForm([
            'pegawai_id' => $pegawai->id,
            'gaji_pokok' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['gaji_pokok']);
});

it('rejects negative tunjangan', function () {
    $user = setupUserWithPermissions([
        'ViewAny:SettingGaji', 'View:SettingGaji', 'Create:SettingGaji', 'Update:SettingGaji',
        'Delete:SettingGaji', 'DeleteAny:SettingGaji', 'ForceDelete:SettingGaji',
        'ForceDeleteAny:SettingGaji', 'Restore:SettingGaji', 'RestoreAny:SettingGaji',
        'Replicate:SettingGaji', 'Reorder:SettingGaji',
    ]);
    $this->actingAs($user);

    $pegawai = Pegawai::factory()->create();

    Livewire::test(CreateSettingGaji::class)
        ->fillForm([
            'pegawai_id' => $pegawai->id,
            'gaji_pokok' => 5000000,
            'tunjangan_jabatan' => -100,
        ])
        ->call('create')
        ->assertHasFormErrors(['tunjangan_jabatan']);
});

// ── JurnalUmum: reject when both debit and kredit are 0 ─────────────────────

it('rejects jurnal umum when both debit and kredit are zero', function () {
    $user = setupUserWithPermissions([
        'ViewAny:JurnalUmum', 'View:JurnalUmum', 'Create:JurnalUmum', 'Update:JurnalUmum',
        'Delete:JurnalUmum', 'DeleteAny:JurnalUmum', 'ForceDelete:JurnalUmum',
        'ForceDeleteAny:JurnalUmum', 'Restore:JurnalUmum', 'RestoreAny:JurnalUmum',
        'Replicate:JurnalUmum', 'Reorder:JurnalUmum',
    ]);
    $this->actingAs($user);

    $akun = Akun::factory()->create();

    Livewire::test(CreateJurnalUmum::class)
        ->fillForm([
            'nomor_bukti' => 'JU-TEST-001',
            'tanggal' => now()->format('Y-m-d'),
            'akun_id' => $akun->id,
            'keterangan' => 'Test jurnal',
            'debit' => 0,
            'kredit' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['debit']);
});

it('accepts jurnal umum with nonzero debit', function () {
    $user = setupUserWithPermissions([
        'ViewAny:JurnalUmum', 'View:JurnalUmum', 'Create:JurnalUmum', 'Update:JurnalUmum',
        'Delete:JurnalUmum', 'DeleteAny:JurnalUmum', 'ForceDelete:JurnalUmum',
        'ForceDeleteAny:JurnalUmum', 'Restore:JurnalUmum', 'RestoreAny:JurnalUmum',
        'Replicate:JurnalUmum', 'Reorder:JurnalUmum',
    ]);
    $this->actingAs($user);

    $akun = Akun::factory()->create();

    Livewire::test(CreateJurnalUmum::class)
        ->fillForm([
            'nomor_bukti' => 'JU-TEST-002',
            'tanggal' => now()->format('Y-m-d'),
            'akun_id' => $akun->id,
            'keterangan' => 'Test jurnal valid',
            'debit' => 100000,
            'kredit' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors(['debit', 'kredit']);
});
