<?php

use App\Models\Pegawai;
use App\Models\SettingGaji;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── bcmath accessor tests ─────────────────────────────────────────────────────

it('getTotalTunjanganAttribute sums correctly with bcmath precision', function () {
    $setting = new SettingGaji([
        'tunjangan_jabatan' => '100000.10',
        'tunjangan_kehadiran' => '200000.20',
        'tunjangan_transport' => '50000.05',
        'tunjangan_makan' => '75000.15',
        'tunjangan_lainnya' => '25000.50',
    ]);

    expect($setting->total_tunjangan)->toBe('450001.00');
});

it('getTotalPotonganAttribute sums correctly with bcmath precision', function () {
    $setting = new SettingGaji([
        'potongan_bpjs' => '50000.00',
        'potongan_pph21' => '100000.10',
        'potongan_lainnya' => '25000.40',
    ]);

    expect($setting->total_potongan)->toBe('175000.50');
});

it('getGajiBersihAttribute computes gaji_pokok + total_tunjangan - total_potongan', function () {
    $setting = new SettingGaji([
        'gaji_pokok' => '5000000.00',
        'tunjangan_jabatan' => '500000.00',
        'tunjangan_kehadiran' => '0.00',
        'tunjangan_transport' => '0.00',
        'tunjangan_makan' => '0.00',
        'tunjangan_lainnya' => '0.00',
        'potongan_bpjs' => '250000.00',
        'potongan_pph21' => '0.00',
        'potongan_lainnya' => '0.00',
    ]);

    // 5000000 + 500000 - 250000 = 5250000
    expect($setting->gaji_bersih)->toBe('5250000.00');
});

// ── activate() tests ──────────────────────────────────────────────────────────

it('activate() sets this setting active and deactivates others for the same pegawai', function () {
    $pegawai = Pegawai::factory()->create();

    $old = SettingGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'is_active' => true,
        'gaji_pokok' => '3000000.00',
        'tunjangan_jabatan' => '0', 'tunjangan_kehadiran' => '0',
        'tunjangan_transport' => '0', 'tunjangan_makan' => '0', 'tunjangan_lainnya' => '0',
        'potongan_bpjs' => '0', 'potongan_pph21' => '0', 'potongan_lainnya' => '0',
    ]);

    $new = SettingGaji::factory()->create([
        'pegawai_id' => $pegawai->id,
        'is_active' => false,
        'gaji_pokok' => '4000000.00',
        'tunjangan_jabatan' => '0', 'tunjangan_kehadiran' => '0',
        'tunjangan_transport' => '0', 'tunjangan_makan' => '0', 'tunjangan_lainnya' => '0',
        'potongan_bpjs' => '0', 'potongan_pph21' => '0', 'potongan_lainnya' => '0',
    ]);

    $new->activate();

    expect($new->fresh()->is_active)->toBeTrue();
    expect($old->fresh()->is_active)->toBeFalse();
});

it('activate() does not deactivate settings for a different pegawai', function () {
    $pegawai1 = Pegawai::factory()->create();
    $pegawai2 = Pegawai::factory()->create();

    $row = fn ($pid) => [
        'pegawai_id' => $pid, 'is_active' => true, 'gaji_pokok' => '1000000.00',
        'tunjangan_jabatan' => '0', 'tunjangan_kehadiran' => '0',
        'tunjangan_transport' => '0', 'tunjangan_makan' => '0', 'tunjangan_lainnya' => '0',
        'potongan_bpjs' => '0', 'potongan_pph21' => '0', 'potongan_lainnya' => '0',
    ];

    $s1 = SettingGaji::factory()->create($row($pegawai1->id));
    $s2 = SettingGaji::factory()->create(array_merge($row($pegawai2->id), ['is_active' => false]));

    $s2->activate();

    expect($s1->fresh()->is_active)->toBeTrue(); // different pegawai — untouched
    expect($s2->fresh()->is_active)->toBeTrue();
});

it('scopeActiveForPegawai returns only active settings for that pegawai', function () {
    $pegawai = Pegawai::factory()->create();

    $base = [
        'gaji_pokok' => '0', 'tunjangan_jabatan' => '0', 'tunjangan_kehadiran' => '0',
        'tunjangan_transport' => '0', 'tunjangan_makan' => '0', 'tunjangan_lainnya' => '0',
        'potongan_bpjs' => '0', 'potongan_pph21' => '0', 'potongan_lainnya' => '0',
    ];

    SettingGaji::factory()->create(array_merge($base, ['pegawai_id' => $pegawai->id, 'is_active' => true]));
    SettingGaji::factory()->create(array_merge($base, ['pegawai_id' => $pegawai->id, 'is_active' => false]));

    $results = SettingGaji::activeForPegawai($pegawai->id)->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->is_active)->toBeTrue();
});
