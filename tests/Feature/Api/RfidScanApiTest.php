<?php

use App\Models\KartuRfid;
use App\Models\PresensiHarian;
use App\Models\RfidDevice;
use App\Models\RfidScanLog;
use App\Models\Sekolah;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createDevice(string $jenis = 'serbaguna'): array
{
    $plain = Str::random(60);
    $device = RfidDevice::factory()->create([
        'jenis' => $jenis,
        'api_token' => Hash::make($plain),
        'is_active' => true,
    ]);

    return [$device, $plain];
}

function createSekolahWithConfig(): Sekolah
{
    return Sekolah::factory()->create([
        'jam_masuk_default' => '07:00:00',
        'batas_terlambat_menit' => 15,
        'jam_pulang_minimal' => '12:00:00',
        'debounce_scan_detik' => 60,
    ]);
}

beforeEach(function () {
    createSekolahWithConfig();
});

it('returns 401 without bearer token', function () {
    $response = $this->postJson('/api/rfid/scan', ['uid' => '04A1B2C3']);
    $response->assertUnauthorized();
});

it('returns 401 with invalid token', function () {
    [, $plain] = createDevice();

    $response = $this->withHeaders(['Authorization' => 'Bearer wrong-token-here'])
        ->postJson('/api/rfid/scan', ['uid' => '04A1B2C3']);

    $response->assertUnauthorized();
});

it('returns 401 when device is inactive', function () {
    $plain = Str::random(60);
    RfidDevice::factory()->nonaktif()->create(['api_token' => Hash::make($plain)]);

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', ['uid' => '04A1B2C3']);

    $response->assertUnauthorized();
});

it('logs and rejects unknown UID', function () {
    [, $plain] = createDevice();

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', ['uid' => 'FFFFFFFF']);

    $response->assertOk()
        ->assertJsonPath('success', false)
        ->assertJsonPath('jenis', 'tidak_dikenal');

    expect(RfidScanLog::where('jenis', 'tidak_dikenal')->count())->toBe(1);
});

it('logs and rejects scan from inactive card', function () {
    [, $plain] = createDevice();
    $kartu = KartuRfid::factory()->hilang()->create(['uid' => '04A1B2C3']);

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', ['uid' => '04A1B2C3']);

    $response->assertOk()
        ->assertJsonPath('jenis', 'ditolak')
        ->assertJsonPath('success', false);

    expect(RfidScanLog::where('kartu_rfid_id', $kartu->id)->where('jenis', 'ditolak')->count())->toBe(1);
});

it('creates presensi with hadir status when first tap is on time', function () {
    [, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T06:55:00+07:00',
        ]);

    $response->assertOk()
        ->assertJsonPath('jenis', 'masuk')
        ->assertJsonPath('presensi.status', 'hadir');

    $record = PresensiHarian::where('siswa_id', $siswa->id)->first();
    expect($record)->not->toBeNull();
    expect($record->tanggal->toDateString())->toBe('2026-05-24');
    expect($record->status)->toBe('hadir');
    expect($record->sumber_masuk)->toBe('rfid');
});

it('creates presensi with terlambat status when first tap exceeds threshold', function () {
    [, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    Carbon::setTestNow(Carbon::parse('2026-05-24 07:25:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T07:25:00+07:00',
        ]);

    $response->assertOk()
        ->assertJsonPath('jenis', 'masuk')
        ->assertJsonPath('presensi.status', 'terlambat');

    $record = PresensiHarian::where('siswa_id', $siswa->id)->first();
    expect($record->terlambat_menit)->toBe(25);
});

it('updates jam_pulang on second tap after jam_pulang_minimal', function () {
    [, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    PresensiHarian::factory()->for($siswa)->create([
        'tanggal' => '2026-05-24',
        'jam_masuk' => '06:55:00',
        'jam_pulang' => null,
        'status' => 'hadir',
        'sumber_masuk' => 'rfid',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-05-24 13:05:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T13:05:00+07:00',
        ]);

    $response->assertOk()->assertJsonPath('jenis', 'pulang');

    $record = PresensiHarian::where('siswa_id', $siswa->id)->first();
    expect($record->jam_pulang->format('H:i:s'))->toBe('13:05:00');
    expect($record->sumber_pulang)->toBe('rfid');
});

it('rejects pulang tap before jam_pulang_minimal', function () {
    [, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    PresensiHarian::factory()->for($siswa)->create([
        'tanggal' => '2026-05-24',
        'jam_masuk' => '06:55:00',
        'jam_pulang' => null,
    ]);

    Carbon::setTestNow(Carbon::parse('2026-05-24 11:30:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T11:30:00+07:00',
        ]);

    $response->assertOk()
        ->assertJsonPath('jenis', 'ditolak')
        ->assertJsonPath('success', false);

    $record = PresensiHarian::where('siswa_id', $siswa->id)->first();
    expect($record->jam_pulang)->toBeNull();
});

it('returns duplikat for tap within debounce window', function () {
    [, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));
    $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T06:55:00+07:00',
        ]);

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:30'));
    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T06:55:30+07:00',
        ]);

    $response->assertOk()->assertJsonPath('jenis', 'duplikat');
});

it('rejects third tap when both masuk and pulang already recorded', function () {
    [, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    PresensiHarian::factory()->for($siswa)->create([
        'tanggal' => '2026-05-24',
        'jam_masuk' => '06:55:00',
        'jam_pulang' => '13:00:00',
    ]);

    Carbon::setTestNow(Carbon::parse('2026-05-24 14:00:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T14:00:00+07:00',
        ]);

    $response->assertOk()->assertJsonPath('jenis', 'duplikat');
});

it('logs every scan attempt to rfid_scan_logs (audit completeness)', function () {
    [, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));

    $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T06:55:00+07:00',
        ]);

    $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', ['uid' => 'FFFFFFFF']);

    expect(RfidScanLog::count())->toBe(2);
});

it('authenticates a device using the token_prefix fast-path', function () {
    $plain = Str::random(60);
    $device = RfidDevice::factory()->create([
        'api_token' => Hash::make($plain),
        'token_prefix' => substr($plain, 0, 8),
        'is_active' => true,
    ]);
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T06:55:00+07:00',
        ]);

    $response->assertOk()->assertJsonPath('jenis', 'masuk');
    expect($device->fresh()->terakhir_aktif)->not->toBeNull();
});

it('still authenticates legacy devices with null token_prefix', function () {
    $plain = Str::random(60);
    RfidDevice::factory()->create([
        'api_token' => Hash::make($plain),
        'token_prefix' => null,
        'is_active' => true,
    ]);
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T06:55:00+07:00',
        ]);

    $response->assertOk()->assertJsonPath('jenis', 'masuk');
});

it('rejects scanned_at too far in the future', function () {
    [, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T07:05:00+07:00',
        ]);

    $response->assertStatus(422)->assertJsonValidationErrorFor('scanned_at');
});

it('rejects scanned_at older than one day', function () {
    [, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));

    $response = $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-22T06:55:00+07:00',
        ]);

    $response->assertStatus(422)->assertJsonValidationErrorFor('scanned_at');
});

it('updates device terakhir_aktif on successful scan', function () {
    [$device, $plain] = createDevice();
    $siswa = Siswa::factory()->create();
    KartuRfid::factory()->create(['owner_type' => Siswa::class, 'owner_id' => $siswa->id, 'uid' => '04A1B2C3']);

    expect($device->terakhir_aktif)->toBeNull();

    Carbon::setTestNow(Carbon::parse('2026-05-24 06:55:00'));

    $this->withHeaders(['Authorization' => 'Bearer '.$plain])
        ->postJson('/api/rfid/scan', [
            'uid' => '04A1B2C3',
            'scanned_at' => '2026-05-24T06:55:00+07:00',
        ]);

    expect($device->fresh()->terakhir_aktif)->not->toBeNull();
});
