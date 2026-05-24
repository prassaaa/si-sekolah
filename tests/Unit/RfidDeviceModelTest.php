<?php

use App\Models\RfidDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('generates a plain token and stores hashed value', function () {
    $device = RfidDevice::factory()->create();

    $plain = $device->generateToken();

    expect($plain)->toBeString()->toHaveLength(60);
    expect($device->fresh()->api_token)->not->toBe($plain);
    expect(Hash::check($plain, $device->fresh()->api_token))->toBeTrue();
});

it('verifies a valid plain token', function () {
    $device = RfidDevice::factory()->create();
    $plain = $device->generateToken();

    expect($device->fresh()->verifyToken($plain))->toBeTrue();
});

it('rejects invalid plain token', function () {
    $device = RfidDevice::factory()->create();
    $device->generateToken();

    expect($device->fresh()->verifyToken('wrong-token'))->toBeFalse();
});

it('hides api_token from array representation', function () {
    $device = RfidDevice::factory()->create();

    expect($device->toArray())->not->toHaveKey('api_token');
});

it('aktif scope returns only active devices', function () {
    RfidDevice::factory()->create();
    RfidDevice::factory()->nonaktif()->create();
    RfidDevice::factory()->create();

    expect(RfidDevice::aktif()->count())->toBe(2);
});

it('tandaiAktif updates terakhir_aktif timestamp', function () {
    $device = RfidDevice::factory()->create(['terakhir_aktif' => null]);

    expect($device->terakhir_aktif)->toBeNull();

    $device->tandaiAktif();

    expect($device->fresh()->terakhir_aktif)->not->toBeNull();
});

it('jenisInfo accessor returns correct labels', function () {
    $masuk = RfidDevice::factory()->masuk()->create();
    $pulang = RfidDevice::factory()->pulang()->create();

    expect($masuk->jenis_info)->toBe(['label' => 'Gerbang Masuk', 'color' => 'success']);
    expect($pulang->jenis_info)->toBe(['label' => 'Gerbang Pulang', 'color' => 'info']);
});
