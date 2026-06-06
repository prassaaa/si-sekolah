<?php

use App\Models\KartuRfid;
use App\Models\PresensiHarian;
use App\Models\RfidDevice;
use App\Models\RfidScanLog;
use App\Models\Sekolah;
use App\Models\Siswa;
use App\Services\Rfid\PresensiScanService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeServiceContext(): array
{
    Sekolah::factory()->create([
        'jam_masuk_default' => '07:00:00',
        'batas_terlambat_menit' => 15,
        'jam_pulang_minimal' => '12:00:00',
        'debounce_scan_detik' => 60,
    ]);

    $service = new PresensiScanService;
    $device = RfidDevice::factory()->create();

    return [$service, $device];
}

it('handles unknown UID with tidak_dikenal log', function () {
    [$service, $device] = makeServiceContext();

    $result = $service->handle($device, 'FFFFFFFF', Carbon::parse('2026-05-24 06:55:00'));

    expect($result['jenis'])->toBe('tidak_dikenal');
    expect($result['success'])->toBeFalse();
    expect(RfidScanLog::where('jenis', 'tidak_dikenal')->count())->toBe(1);
});

it('handles inactive card with ditolak log', function () {
    [$service, $device] = makeServiceContext();
    KartuRfid::factory()->hilang()->create(['uid' => '04A1B2C3']);

    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 06:55:00'));

    expect($result['jenis'])->toBe('ditolak');
    expect($result['success'])->toBeFalse();
});

it('creates first masuk record with hadir status', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create([
        'owner_type' => Siswa::class,
        'owner_id' => $siswa->id,
        'uid' => '04A1B2C3',
    ]);

    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 06:55:00'));

    expect($result['jenis'])->toBe('masuk');
    expect($result['presensi']['status'])->toBe('hadir');
    expect($result['presensi']['terlambat_menit'])->toBeNull();

    $record = PresensiHarian::where('siswa_id', $siswa->id)->first();
    expect($record->jam_masuk->format('H:i:s'))->toBe('06:55:00');
});

it('creates first masuk record with terlambat status when past threshold', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 07:30:00'));

    expect($result['presensi']['status'])->toBe('terlambat');
    expect($result['presensi']['terlambat_menit'])->toBe(30);
});

it('treats tap exactly at jam_masuk_default as hadir', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 07:00:00'));

    expect($result['presensi']['status'])->toBe('hadir');
});

it('treats tap exactly at threshold boundary as hadir', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 07:15:00'));

    expect($result['presensi']['status'])->toBe('hadir');
});

it('updates jam_pulang on second tap after minimum hour', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    PresensiHarian::factory()->for($siswa)->create([
        'tanggal' => '2026-05-24',
        'jam_masuk' => '06:55:00',
        'jam_pulang' => null,
    ]);

    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 13:05:00'));

    expect($result['jenis'])->toBe('pulang');
    expect($result['presensi']['jam_pulang'])->toBe('13:05:00');
});

it('rejects pulang tap before minimum hour', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    PresensiHarian::factory()->for($siswa)->create([
        'tanggal' => '2026-05-24',
        'jam_masuk' => '06:55:00',
        'jam_pulang' => null,
    ]);

    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 11:30:00'));

    expect($result['jenis'])->toBe('ditolak');
});

it('returns duplikat for tap within debounce window', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 06:55:00'));
    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 06:55:30'));

    expect($result['jenis'])->toBe('duplikat');
});

it('rejects third tap when both masuk and pulang exist', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    PresensiHarian::factory()->for($siswa)->create([
        'tanggal' => '2026-05-24',
        'jam_masuk' => '06:55:00',
        'jam_pulang' => '13:00:00',
    ]);

    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 14:00:00'));

    expect($result['jenis'])->toBe('duplikat');
});

it('normalizes UID variants to single canonical form', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    $result = $service->handle($device, '04:a1:b2:c3', Carbon::parse('2026-05-24 06:55:00'));

    expect($result['jenis'])->toBe('masuk');
});

it('always writes a scan log regardless of outcome', function () {
    [$service, $device] = makeServiceContext();

    $service->handle($device, 'AAAAAAAA', Carbon::parse('2026-05-24 06:55:00'));
    $service->handle($device, 'BBBBBBBB', Carbon::parse('2026-05-24 06:56:00'));

    expect(RfidScanLog::count())->toBe(2);
});

it('returns duplikat when two taps share the identical timestamp', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    $first = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 06:55:00'));
    $second = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 06:55:00'));

    expect($first['jenis'])->toBe('masuk');
    expect($second['jenis'])->toBe('duplikat');
});

it('applies default debounce when no Sekolah row exists', function () {
    Sekolah::query()->delete();

    $service = new PresensiScanService;
    $device = RfidDevice::factory()->create();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    $first = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 06:55:00'));
    $second = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 06:55:01'));

    expect($first['jenis'])->toBe('masuk');
    expect($second['jenis'])->toBe('duplikat');
});

it('does not treat a manual presensi with null jam_masuk as an open masuk for pulang', function () {
    [$service, $device] = makeServiceContext();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    PresensiHarian::factory()->for($siswa)->create([
        'tanggal' => '2026-05-24',
        'jam_masuk' => null,
        'jam_pulang' => null,
        'status' => 'izin',
    ]);

    $result = $service->handle($device, '04A1B2C3', Carbon::parse('2026-05-24 13:05:00'));

    expect($result['jenis'])->toBe('ditolak');
    expect($result['success'])->toBeFalse();

    $record = PresensiHarian::where('siswa_id', $siswa->id)->first();
    expect($record->jam_pulang)->toBeNull();
});
