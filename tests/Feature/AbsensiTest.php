<?php

use App\Filament\Resources\Absensis\AbsensiResource;
use App\Filament\Resources\Absensis\Pages\CreateAbsensi;
use App\Filament\Resources\Absensis\Pages\EditAbsensi;
use App\Filament\Resources\Absensis\Pages\InputAbsensi;
use App\Filament\Resources\Absensis\Pages\ListAbsensis;
use App\Filament\Resources\Absensis\Pages\ViewAbsensi;
use App\Models\Absensi;
use App\Models\JadwalPelajaran;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permissions = [
        'ViewAny:Absensi',
        'View:Absensi',
        'Create:Absensi',
        'Update:Absensi',
        'Delete:Absensi',
        'DeleteAny:Absensi',
        'ForceDelete:Absensi',
        'ForceDeleteAny:Absensi',
        'Restore:Absensi',
        'RestoreAny:Absensi',
        'Replicate:Absensi',
        'Reorder:Absensi',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('can render the list page', function () {
    Livewire::test(ListAbsensis::class)
        ->assertOk();
});

it('can list absensi records', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    $absensis = Absensi::factory()
        ->count(3)
        ->sequence(
            ['tanggal' => '2026-02-18'],
            ['tanggal' => '2026-02-19'],
            ['tanggal' => '2026-02-20'],
        )
        ->create([
            'jadwal_pelajaran_id' => $jadwal->id,
            'siswa_id' => $siswa->id,
        ]);

    Livewire::test(ListAbsensis::class)
        ->assertOk()
        ->assertCanSeeTableRecords($absensis);
});

it('can render the create page', function () {
    Livewire::test(CreateAbsensi::class)
        ->assertOk();
});

it('can create an absensi record', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    Livewire::test(CreateAbsensi::class)
        ->fillForm([
            'kelas_id' => $kelas->id,
            'jadwal_pelajaran_id' => $jadwal->id,
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-02-20',
            'status' => 'hadir',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    $this->assertDatabaseHas('absensis', [
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa->id,
        'status' => 'hadir',
    ]);

    expect(Absensi::where('jadwal_pelajaran_id', $jadwal->id)
        ->where('siswa_id', $siswa->id)
        ->whereDate('tanggal', '2026-02-20')
        ->exists())->toBeTrue();
});

it('validates required fields on create', function (array $data, array $errors) {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    Livewire::test(CreateAbsensi::class)
        ->fillForm([
            'kelas_id' => $kelas->id,
            'jadwal_pelajaran_id' => $jadwal->id,
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-02-20',
            'status' => 'hadir',
            ...$data,
        ])
        ->call('create')
        ->assertHasFormErrors($errors);
})->with([
    'jadwal_pelajaran_id is required' => [['jadwal_pelajaran_id' => null], ['jadwal_pelajaran_id' => 'required']],
    'siswa_id is required' => [['siswa_id' => null], ['siswa_id' => 'required']],
    'tanggal is required' => [['tanggal' => null], ['tanggal' => 'required']],
    'status is required' => [['status' => null], ['status' => 'required']],
]);

it('rejects create when siswa is not in jadwal class', function () {
    $kelasA = Kelas::factory()->create([
        'nama' => 'KLS-A',
    ]);
    $kelasB = Kelas::factory()->create([
        'tahun_ajaran_id' => $kelasA->tahun_ajaran_id,
        'nama' => 'KLS-B',
    ]);
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelasA->id, 'is_active' => true]);
    $siswaKelasB = Siswa::factory()->create(['kelas_id' => $kelasB->id, 'is_active' => true]);

    Livewire::test(CreateAbsensi::class)
        ->fillForm([
            'kelas_id' => $kelasA->id,
            'jadwal_pelajaran_id' => $jadwal->id,
            'siswa_id' => $siswaKelasB->id,
            'tanggal' => '2026-02-20',
            'status' => 'hadir',
        ])
        ->call('create')
        ->assertHasFormErrors(['siswa_id']);

    expect(Absensi::count())->toBe(0);
});

it('rejects duplicate create for same jadwal siswa and tanggal', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);

    Absensi::factory()->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa->id,
        'tanggal' => '2026-02-20',
    ]);

    Livewire::test(CreateAbsensi::class)
        ->fillForm([
            'kelas_id' => $kelas->id,
            'jadwal_pelajaran_id' => $jadwal->id,
            'siswa_id' => $siswa->id,
            'tanggal' => '2026-02-20',
            'status' => 'hadir',
        ])
        ->call('create')
        ->assertHasErrors();

    expect(Absensi::count())->toBe(1);
});

it('can render the view page', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    $absensi = Absensi::factory()->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa->id,
    ]);

    Livewire::test(ViewAbsensi::class, ['record' => $absensi->id])
        ->assertOk();
});

it('can render the edit page', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    $absensi = Absensi::factory()->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa->id,
    ]);

    Livewire::test(EditAbsensi::class, ['record' => $absensi->id])
        ->assertOk();
});

it('can update an absensi record', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    $absensi = Absensi::factory()->hadir()->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa->id,
    ]);

    Livewire::test(EditAbsensi::class, ['record' => $absensi->id])
        ->fillForm([
            'status' => 'sakit',
            'keterangan' => 'Demam tinggi',
        ])
        ->call('save')
        ->assertNotified();

    $this->assertDatabaseHas('absensis', [
        'id' => $absensi->id,
        'status' => 'sakit',
        'keterangan' => 'Demam tinggi',
    ]);
});

it('rejects update when siswa is not in jadwal class', function () {
    $kelasA = Kelas::factory()->create([
        'nama' => 'KLS-C',
    ]);
    $kelasB = Kelas::factory()->create([
        'tahun_ajaran_id' => $kelasA->tahun_ajaran_id,
        'nama' => 'KLS-D',
    ]);
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelasA->id, 'is_active' => true]);
    $siswaA = Siswa::factory()->create(['kelas_id' => $kelasA->id, 'is_active' => true]);
    $siswaB = Siswa::factory()->create(['kelas_id' => $kelasB->id, 'is_active' => true]);

    $absensi = Absensi::factory()->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswaA->id,
        'tanggal' => '2026-02-20',
    ]);

    Livewire::test(EditAbsensi::class, ['record' => $absensi->id])
        ->fillForm([
            'siswa_id' => $siswaB->id,
        ])
        ->call('save')
        ->assertHasFormErrors(['siswa_id']);

    expect(Absensi::find($absensi->id)?->siswa_id)->toBe($siswaA->id);
});

it('can filter absensi by status', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id]);
    $siswa1 = Siswa::factory()->create(['kelas_id' => $kelas->id]);
    $siswa2 = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    $hadirRecord = Absensi::factory()->hadir()->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa1->id,
        'tanggal' => '2026-02-20',
    ]);

    $alphaRecord = Absensi::factory()->alpha()->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa2->id,
        'tanggal' => '2026-02-20',
    ]);

    Livewire::test(ListAbsensis::class)
        ->filterTable('status', 'alpha')
        ->assertCanSeeTableRecords([$alphaRecord])
        ->assertCanNotSeeTableRecords([$hadirRecord]);
});

it('can render the input absensi page', function () {
    Livewire::test(InputAbsensi::class)
        ->assertOk();
});

it('can bulk input absensi via input page', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);
    $siswa1 = Siswa::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);
    $siswa2 = Siswa::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);

    $tanggal = '2026-02-20';

    Livewire::test(InputAbsensi::class)
        ->fillForm([
            'kelas_id' => $kelas->id,
            'jadwal_pelajaran_id' => $jadwal->id,
            'tanggal' => $tanggal,
            'absensi' => [
                ['siswa_id' => $siswa1->id, 'status' => 'hadir', 'keterangan' => ''],
                ['siswa_id' => $siswa2->id, 'status' => 'alpha', 'keterangan' => ''],
            ],
        ])
        ->call('simpan')
        ->assertNotified();

    $this->assertDatabaseHas('absensis', [
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa1->id,
        'status' => 'hadir',
    ]);

    $this->assertDatabaseHas('absensis', [
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa2->id,
        'status' => 'alpha',
    ]);

    expect(Absensi::count())->toBe(2);
});

it('rejects bulk input when there are students outside jadwal class and keeps data atomic', function () {
    $kelasA = Kelas::factory()->create([
        'nama' => 'KLS-E',
    ]);
    $kelasB = Kelas::factory()->create([
        'tahun_ajaran_id' => $kelasA->tahun_ajaran_id,
        'nama' => 'KLS-F',
    ]);
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelasA->id, 'is_active' => true]);

    $siswaValid = Siswa::factory()->create(['kelas_id' => $kelasA->id, 'is_active' => true]);
    $siswaInvalid = Siswa::factory()->create(['kelas_id' => $kelasB->id, 'is_active' => true]);

    Livewire::test(InputAbsensi::class)
        ->fillForm([
            'kelas_id' => $kelasA->id,
            'jadwal_pelajaran_id' => $jadwal->id,
            'tanggal' => '2026-02-20',
            'absensi' => [
                ['siswa_id' => $siswaValid->id, 'status' => 'hadir', 'keterangan' => ''],
                ['siswa_id' => $siswaInvalid->id, 'status' => 'izin', 'keterangan' => 'Test tamper'],
            ],
        ])
        ->call('simpan')
        ->assertHasErrors(['absensi']);

    expect(Absensi::count())->toBe(0);
});

it('rejects bulk input when daftar siswa tidak lengkap', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);

    $siswa1 = Siswa::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);
    Siswa::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);

    Livewire::test(InputAbsensi::class)
        ->fillForm([
            'kelas_id' => $kelas->id,
            'jadwal_pelajaran_id' => $jadwal->id,
            'tanggal' => '2026-02-20',
            'absensi' => [
                ['siswa_id' => $siswa1->id, 'status' => 'hadir', 'keterangan' => ''],
            ],
        ])
        ->call('simpan')
        ->assertHasErrors(['absensi']);

    expect(Absensi::count())->toBe(0);
});

it('can update existing absensi via input page', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id, 'is_active' => true]);
    $tanggal = '2026-02-20';

    Absensi::factory()->hadir()->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa->id,
        'tanggal' => $tanggal,
    ]);

    Livewire::test(InputAbsensi::class)
        ->fillForm([
            'kelas_id' => $kelas->id,
            'jadwal_pelajaran_id' => $jadwal->id,
            'tanggal' => $tanggal,
            'absensi' => [
                ['siswa_id' => $siswa->id, 'status' => 'izin', 'keterangan' => 'Acara keluarga'],
            ],
        ])
        ->call('simpan')
        ->assertNotified();

    $this->assertDatabaseHas('absensis', [
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa->id,
        'status' => 'izin',
        'keterangan' => 'Acara keluarga',
    ]);

    expect(Absensi::where('jadwal_pelajaran_id', $jadwal->id)
        ->where('siswa_id', $siswa->id)
        ->count())->toBe(1);
});

it('shows navigation badge for alpha count', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id]);
    $siswa1 = Siswa::factory()->create(['kelas_id' => $kelas->id]);
    $siswa2 = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    Absensi::factory()->alpha()->count(3)->sequence(
        ['tanggal' => '2026-02-18'],
        ['tanggal' => '2026-02-19'],
        ['tanggal' => '2026-02-20'],
    )->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa1->id,
    ]);

    Absensi::factory()->hadir()->count(2)->sequence(
        ['tanggal' => '2026-02-18'],
        ['tanggal' => '2026-02-19'],
    )->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa2->id,
    ]);

    expect(AbsensiResource::getNavigationBadge())->toBe('3');
    expect(AbsensiResource::getNavigationBadgeColor())->toBe('danger');
});

it('returns null navigation badge when no alpha', function () {
    $kelas = Kelas::factory()->create();
    $jadwal = JadwalPelajaran::factory()->create(['kelas_id' => $kelas->id]);
    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    Absensi::factory()->hadir()->count(3)->sequence(
        ['tanggal' => '2026-02-18'],
        ['tanggal' => '2026-02-19'],
        ['tanggal' => '2026-02-20'],
    )->create([
        'jadwal_pelajaran_id' => $jadwal->id,
        'siswa_id' => $siswa->id,
    ]);

    expect(AbsensiResource::getNavigationBadge())->toBeNull();
});
