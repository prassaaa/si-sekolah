<?php

use App\Models\PresensiHarian;
use App\Models\Siswa;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('hariIni scope returns only today records', function () {
    $siswa = Siswa::factory()->create();

    PresensiHarian::factory()->for($siswa)->create(['tanggal' => today()]);
    PresensiHarian::factory()->create(['tanggal' => today()->subDays(3)]);
    PresensiHarian::factory()->create(['tanggal' => today()->subDays(10)]);

    expect(PresensiHarian::hariIni()->count())->toBe(1);
});

it('terlambatSaja scope returns only terlambat records', function () {
    PresensiHarian::factory()->hadir()->create();
    PresensiHarian::factory()->terlambat(15)->create();
    PresensiHarian::factory()->alpha()->create();

    expect(PresensiHarian::terlambatSaja()->count())->toBe(1);
});

it('isHadir returns true for hadir and terlambat', function () {
    $hadir = PresensiHarian::factory()->hadir()->make();
    $terlambat = PresensiHarian::factory()->terlambat()->make();
    $alpha = PresensiHarian::factory()->alpha()->make();
    $izin = PresensiHarian::factory()->izin()->make();

    expect($hadir->isHadir())->toBeTrue();
    expect($terlambat->isHadir())->toBeTrue();
    expect($alpha->isHadir())->toBeFalse();
    expect($izin->isHadir())->toBeFalse();
});

it('sudahPulang reflects jam_pulang state', function () {
    $belumPulang = PresensiHarian::factory()->make([
        'jam_masuk' => '07:00:00',
        'jam_pulang' => null,
    ]);
    $sudahPulang = PresensiHarian::factory()->make([
        'jam_masuk' => '07:00:00',
        'jam_pulang' => '13:00:00',
    ]);

    expect($belumPulang->sudahPulang())->toBeFalse();
    expect($sudahPulang->sudahPulang())->toBeTrue();
});

it('statusInfo accessor returns correct labels per status', function () {
    $hadir = PresensiHarian::factory()->hadir()->make();
    $terlambat = PresensiHarian::factory()->terlambat()->make();
    $alpha = PresensiHarian::factory()->alpha()->make();

    expect($hadir->status_info)->toBe(['label' => 'Hadir', 'color' => 'success']);
    expect($terlambat->status_info)->toBe(['label' => 'Terlambat', 'color' => 'warning']);
    expect($alpha->status_info)->toBe(['label' => 'Alpha', 'color' => 'danger']);
});

it('enforces unique siswa_id and tanggal at database level', function () {
    $siswa = Siswa::factory()->create();
    $tanggal = today();

    PresensiHarian::factory()->for($siswa)->create(['tanggal' => $tanggal]);

    expect(fn () => PresensiHarian::factory()->for($siswa)->create([
        'tanggal' => $tanggal,
    ]))->toThrow(QueryException::class);
});

it('statusOptions returns all status keys', function () {
    expect(PresensiHarian::statusOptions())->toBe([
        'hadir' => 'Hadir',
        'terlambat' => 'Terlambat',
        'izin' => 'Izin',
        'sakit' => 'Sakit',
        'alpha' => 'Alpha',
    ]);
});
