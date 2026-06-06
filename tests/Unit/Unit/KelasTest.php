<?php

use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('getJumlahSiswaAttribute counts only active students', function () {
    $ta = TahunAjaran::factory()->create();
    $kelas = Kelas::factory()->create(['tahun_ajaran_id' => $ta->id]);

    Siswa::factory()->count(3)->create(['kelas_id' => $kelas->id, 'is_active' => true]);
    Siswa::factory()->count(2)->create(['kelas_id' => $kelas->id, 'is_active' => false]);

    expect($kelas->jumlah_siswa)->toBe(3);
});

it('getJumlahSiswaAttribute prefers loaded siswas_count to avoid N+1', function () {
    $ta = TahunAjaran::factory()->create();
    $kelas = Kelas::factory()->create(['tahun_ajaran_id' => $ta->id]);

    Siswa::factory()->count(4)->create(['kelas_id' => $kelas->id, 'is_active' => true]);

    // withCount eager-loads siswas_count; accessor must use it without hitting DB again
    $loaded = Kelas::withCount('siswas')->find($kelas->id);

    // siswas_count from withCount (all students, no active filter)
    expect($loaded->siswas_count)->toBe(4);
    // accessor prefers the pre-loaded count
    expect($loaded->jumlah_siswa)->toBe(4);
});

it('getJumlahSiswaAttribute returns 0 when no students', function () {
    $ta = TahunAjaran::factory()->create();
    $kelas = Kelas::factory()->create(['tahun_ajaran_id' => $ta->id]);

    expect($kelas->jumlah_siswa)->toBe(0);
});
