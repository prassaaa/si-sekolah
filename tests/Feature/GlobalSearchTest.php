<?php

use App\Filament\Resources\Pelanggarans\PelanggaranResource;
use App\Filament\Resources\Siswas\SiswaResource;
use App\Filament\Resources\TagihanSiswas\TagihanSiswaResource;
use App\Models\Kelas;
use App\Models\Pelanggaran;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function aktorPencarianGlobal(): void
{
    test()->seed(RoleSeeder::class);
    $user = User::factory()->create();
    $user->assignRole('super_admin');
    test()->actingAs($user);
    Filament::setCurrentPanel(Filament::getPanel('auth'));
}

/**
 * Menjaga agar pencarian global tidak pernah menabrak kolom yang sebenarnya
 * accessor (bukan kolom DB). Bug seperti recordTitleAttribute = 'jadwal_lengkap'
 * (accessor) membuat WHERE SQL ke kolom tak ada → 500. Test ini menjalankan
 * pencarian global untuk SETIAP resource yang searchable dan memastikan tak ada
 * yang melempar exception.
 */
it('pencarian global berjalan tanpa error untuk semua resource', function (): void {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('super_admin');
    $this->actingAs($user);

    $panel = Filament::getPanel('auth');
    Filament::setCurrentPanel($panel);

    $gagal = [];

    foreach ($panel->getResources() as $resource) {
        if (! $resource::canGloballySearch()) {
            continue;
        }

        try {
            $resource::getGlobalSearchResults('jadwal');
        } catch (Throwable $e) {
            $gagal[] = class_basename($resource).': '.$e->getMessage();
        }
    }

    expect($gagal)->toBe([]);
});

it('menemukan siswa lewat NIS & NISN', function (): void {
    aktorPencarianGlobal();

    $siswa = Siswa::factory()->create([
        'nama' => 'Zulkifli Pencarian',
        'nis' => '99887766',
        'nisn' => '1122334455',
    ]);

    expect(SiswaResource::getGlobalSearchResults('99887766'))->not->toBeEmpty()
        ->and(SiswaResource::getGlobalSearchResults('1122334455'))->not->toBeEmpty()
        ->and(SiswaResource::getGlobalSearchResults('Zulkifli'))->not->toBeEmpty();

    expect($siswa->nis)->toBe('99887766');
});

it('menemukan record terkait siswa lewat nama/NIS siswa (relasi)', function (): void {
    aktorPencarianGlobal();

    $kelas = Kelas::factory()->create();
    $siswa = Siswa::factory()->create([
        'nama' => 'Bambang Relasi',
        'nis' => '55443322',
        'kelas_id' => $kelas->id,
    ]);

    Pelanggaran::factory()->for($siswa)->create();
    TagihanSiswa::factory()->for($siswa)->create();

    // Pelanggaran & Tagihan kini dapat ditemukan lewat nama siswa, bukan hanya
    // jenis/nomor — inti perluasan Tier 1 & 2.
    expect(PelanggaranResource::getGlobalSearchResults('Bambang'))->not->toBeEmpty()
        ->and(TagihanSiswaResource::getGlobalSearchResults('Bambang'))->not->toBeEmpty()
        ->and(TagihanSiswaResource::getGlobalSearchResults('55443322'))->not->toBeEmpty();
});
