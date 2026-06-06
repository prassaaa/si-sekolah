<?php

use App\Models\Pegawai;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── getMasaKerjaAttribute ─────────────────────────────────────────────────────

it('getMasaKerjaAttribute uses now() for active staff', function () {
    $pegawai = Pegawai::factory()->create([
        'tanggal_masuk' => Carbon::now()->subYears(3)->subMonths(2),
        'tanggal_keluar' => null,
    ]);

    expect($pegawai->masa_kerja)->toBe('3 tahun 2 bulan');
});

it('getMasaKerjaAttribute uses tanggal_keluar for resigned staff', function () {
    $masuk = Carbon::parse('2018-01-15');
    $keluar = Carbon::parse('2023-01-15'); // exactly 5 years

    $pegawai = Pegawai::factory()->create([
        'tanggal_masuk' => $masuk,
        'tanggal_keluar' => $keluar,
    ]);

    expect($pegawai->masa_kerja)->toBe('5 tahun 0 bulan');
});

it('getMasaKerjaAttribute returns null when tanggal_masuk is null', function () {
    $pegawai = new Pegawai(['tanggal_masuk' => null]);

    expect($pegawai->masa_kerja)->toBeNull();
});

// ── getNamaLengkapAttribute ───────────────────────────────────────────────────

it('getNamaLengkapAttribute returns nama', function () {
    $pegawai = new Pegawai(['nama' => 'Budi Santoso']);

    expect($pegawai->nama_lengkap)->toBe('Budi Santoso');
});
