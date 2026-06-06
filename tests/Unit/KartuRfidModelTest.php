<?php

use App\Models\KartuRfid;
use App\Models\Pegawai;
use App\Models\Siswa;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('normalizes UID to uppercase without separators', function () {
    $siswa = Siswa::factory()->create();

    $kartu = KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04:a1:b2:c3',
        'diaktifkan_pada' => now(),
    ]);

    expect($kartu->fresh()->uid)->toBe('04A1B2C3');
});

it('normalizes UID with dash separator', function () {
    $siswa = Siswa::factory()->create();

    $kartu = KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04-A1-B2-C3-D4',
        'diaktifkan_pada' => now(),
    ]);

    expect($kartu->fresh()->uid)->toBe('04A1B2C3D4');
});

it('rejects UID that is too short', function () {
    $siswa = Siswa::factory()->create();

    expect(fn () => KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04A1',
        'diaktifkan_pada' => now(),
    ]))->toThrow(InvalidArgumentException::class);
});

it('rejects UID that contains non-hex characters', function () {
    $siswa = Siswa::factory()->create();

    expect(fn () => KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => 'GGGGGGGG',
        'diaktifkan_pada' => now(),
    ]))->toThrow(InvalidArgumentException::class);
});

it('auto-deactivates previous active card when new active card is created', function () {
    $siswa = Siswa::factory()->create();

    $kartuLama = KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04A1B2C3',
        'status' => 'aktif',
        'diaktifkan_pada' => now()->subMonth(),
    ]);

    $kartuBaru = KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '05D4E5F6',
        'status' => 'aktif',
        'diaktifkan_pada' => now(),
    ]);

    expect($kartuLama->fresh()->status)->toBe('nonaktif');
    expect($kartuLama->fresh()->dinonaktifkan_pada)->not->toBeNull();
    expect($kartuBaru->fresh()->status)->toBe('aktif');
});

it('auto-deactivates other active cards when status updated to aktif', function () {
    $siswa = Siswa::factory()->create();

    $kartuA = KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04A1B2C3',
        'status' => 'aktif',
        'diaktifkan_pada' => now()->subMonth(),
    ]);

    $kartuB = KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '05D4E5F6',
        'status' => 'nonaktif',
        'diaktifkan_pada' => now()->subMonth(),
    ]);

    $kartuB->update(['status' => 'aktif']);

    expect($kartuA->fresh()->status)->toBe('nonaktif');
    expect($kartuB->fresh()->status)->toBe('aktif');
});

it('deactivates new owner existing active card when active card owner changes', function () {
    $siswaLama = Siswa::factory()->create();
    $siswaBaru = Siswa::factory()->create();

    $kartuBaru = KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswaBaru->id,
        'uid' => '05D4E5F6',
        'status' => 'aktif',
        'diaktifkan_pada' => now(),
    ]);

    $kartuPindah = KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswaLama->id,
        'uid' => '04A1B2C3',
        'status' => 'aktif',
        'diaktifkan_pada' => now(),
    ]);

    $kartuPindah->update(['owner_id' => $siswaBaru->id]);

    expect($kartuBaru->fresh()->status)->toBe('nonaktif');
    expect($kartuPindah->fresh()->status)->toBe('aktif');
});

it('byUid scope normalizes input', function () {
    $siswa = Siswa::factory()->create();

    KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04A1B2C3',
        'diaktifkan_pada' => now(),
    ]);

    expect(KartuRfid::byUid('04:a1:b2:c3')->exists())->toBeTrue();
    expect(KartuRfid::byUid('04-A1-B2-C3')->exists())->toBeTrue();
    expect(KartuRfid::byUid('FFFFFFFF')->exists())->toBeFalse();
});

it('tandaiHilang updates status and timestamp', function () {
    $kartu = KartuRfid::factory()->create();

    $kartu->tandaiHilang('Hilang di lapangan');

    expect($kartu->fresh()->status)->toBe('hilang');
    expect($kartu->fresh()->dinonaktifkan_pada)->not->toBeNull();
    expect($kartu->fresh()->keterangan)->toBe('Hilang di lapangan');
});

it('supports pegawai as owner', function () {
    $pegawai = Pegawai::factory()->create();

    $kartu = KartuRfid::create([
        'owner_type' => Pegawai::class,
        'owner_id' => $pegawai->id,
        'uid' => '05A1B2C3',
        'diaktifkan_pada' => now(),
    ]);

    expect($kartu->fresh()->owner_type)->toBe(Pegawai::class);
    expect($kartu->fresh()->owner->id)->toBe($pegawai->id);
});

it('does not affect siswa active card when pegawai card is created', function () {
    $siswa = Siswa::factory()->create();
    $pegawai = Pegawai::factory()->create();

    $kartuSiswa = KartuRfid::create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04A1B2C3',
        'status' => 'aktif',
        'diaktifkan_pada' => now(),
    ]);

    KartuRfid::create([
        'owner_type' => Pegawai::class,
        'owner_id' => $pegawai->id,
        'uid' => '05D4E5F6',
        'status' => 'aktif',
        'diaktifkan_pada' => now(),
    ]);

    expect($kartuSiswa->fresh()->status)->toBe('aktif');
});
