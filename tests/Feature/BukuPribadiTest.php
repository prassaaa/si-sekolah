<?php

use App\Filament\Resources\Siswas\Pages\ViewSiswa;
use App\Models\Kelas;
use App\Models\Pelanggaran;
use App\Models\PresensiHarian;
use App\Models\Sekolah;
use App\Models\Semester;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Kesiswaan\BukuPribadiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $permissions = [
        'ViewAny:Siswa', 'View:Siswa', 'Create:Siswa',
        'Update:Siswa', 'Delete:Siswa', 'DeleteAny:Siswa',
        'ForceDelete:Siswa', 'ForceDeleteAny:Siswa',
        'Restore:Siswa', 'RestoreAny:Siswa',
        'Replicate:Siswa', 'Reorder:Siswa',
    ];

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
    }

    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    $this->actingAs($user);
});

it('data() returns correct total_poin', function (): void {
    $siswa = Siswa::factory()->create();
    $semester = Semester::factory()->create();

    Pelanggaran::factory()->for($siswa)->for($semester)->create(['poin' => 10]);
    Pelanggaran::factory()->for($siswa)->for($semester)->create(['poin' => 25]);

    $result = app(BukuPribadiService::class)->data($siswa);

    expect($result['total_poin'])->toBe(35);
});

it('data() returns correct presensi_rekap counts', function (): void {
    $siswa = Siswa::factory()->create();

    PresensiHarian::factory()->for($siswa)->hadir()->count(3)->sequence(
        ['tanggal' => '2026-01-05'],
        ['tanggal' => '2026-01-06'],
        ['tanggal' => '2026-01-07'],
    )->create();
    PresensiHarian::factory()->for($siswa)->alpha()->count(2)->sequence(
        ['tanggal' => '2026-01-08'],
        ['tanggal' => '2026-01-09'],
    )->create();
    PresensiHarian::factory()->for($siswa)->izin()->count(1)->create(['tanggal' => '2026-01-12']);

    $result = app(BukuPribadiService::class)->data($siswa);

    expect($result['presensi_rekap'])->toHaveKey('hadir')
        ->and($result['presensi_rekap']['hadir'])->toBe(3)
        ->and($result['presensi_rekap']['alpha'])->toBe(2)
        ->and($result['presensi_rekap']['izin'])->toBe(1);
});

it('data() contains expected keys', function (): void {
    $siswa = Siswa::factory()->create();

    $result = app(BukuPribadiService::class)->data($siswa);

    expect($result)->toHaveKeys([
        'siswa', 'sekolah', 'konselings', 'pelanggarans',
        'total_poin', 'prestasis', 'tahfidzs', 'presensi_rekap',
    ]);
});

it('pdf() output starts with %PDF', function (): void {
    $siswa = Siswa::factory()->create();

    $output = app(BukuPribadiService::class)->pdf($siswa)->output();

    expect(str_starts_with($output, '%PDF'))->toBeTrue();
});

it('filename() contains nis and slug of nama', function (): void {
    $siswa = Siswa::factory()->create([
        'nis' => '12345',
        'nama' => 'Ahmad Fauzi',
    ]);

    $filename = app(BukuPribadiService::class)->filename($siswa);

    expect($filename)
        ->toContain('12345')
        ->toContain('ahmad-fauzi')
        ->toEndWith('.pdf');
});

it('filename() contains no slash or backslash even when nis has them', function (): void {
    $siswa = Siswa::factory()->create([
        'nis' => '0061/2024',
        'nama' => 'Budi\\Santoso',
    ]);

    $filename = app(BukuPribadiService::class)->filename($siswa);

    expect($filename)
        ->not->toContain('/')
        ->not->toContain('\\')
        ->toEndWith('.pdf');
});

it('pdf() renders for siswa that belongs to a kelas', function (): void {
    $siswa = Siswa::factory()->create([
        'kelas_id' => Kelas::factory()->create()->id,
    ]);

    $output = app(BukuPribadiService::class)->pdf($siswa)->output();

    expect(str_starts_with($output, '%PDF'))->toBeTrue();
});

it('pdf() renders without exception for siswa with no relations and no sekolah row', function (): void {
    $siswa = Siswa::factory()->create();

    expect(fn () => app(BukuPribadiService::class)->pdf($siswa)->output())
        ->not->toThrow(Throwable::class);
});

it('preview route streams pdf inline for authorized user', function (): void {
    $siswa = Siswa::factory()->create();

    $response = $this->get(route('siswa.buku-pribadi', $siswa));

    $response->assertSuccessful();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('preview route forbids user without View:Siswa permission', function (): void {
    $siswa = Siswa::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('siswa.buku-pribadi', $siswa))->assertForbidden();
});

it('preview route forbids guest', function (): void {
    $siswa = Siswa::factory()->create();

    auth()->logout();

    $this->get(route('siswa.buku-pribadi', $siswa))->assertForbidden();
});

it('ViewSiswa has pratinjauBukuPribadi action', function (): void {
    $siswa = Siswa::factory()->create();

    Livewire::test(ViewSiswa::class, ['record' => $siswa->getRouteKey()])
        ->assertActionExists('pratinjauBukuPribadi');
});

it('ViewSiswa has cetakBukuPribadi action', function (): void {
    $siswa = Siswa::factory()->create();

    Livewire::test(ViewSiswa::class, ['record' => $siswa->getRouteKey()])
        ->assertActionExists('cetakBukuPribadi');
});

it('cetakBukuPribadi action executes without errors', function (): void {
    Sekolah::factory()->create();
    $siswa = Siswa::factory()->create();

    Livewire::test(ViewSiswa::class, ['record' => $siswa->getRouteKey()])
        ->callAction('cetakBukuPribadi')
        ->assertHasNoActionErrors();
});
