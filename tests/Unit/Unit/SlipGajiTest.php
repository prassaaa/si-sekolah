<?php

use App\Models\Pegawai;
use App\Models\SlipGaji;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('auto-generates nomor with per-month prefix on create', function () {
    $slip = SlipGaji::factory()->create([
        'nomor' => null,
        'tahun' => 2025,
        'bulan' => 3,
        'pegawai_id' => Pegawai::factory()->create()->id,
        'gaji_pokok' => '5000000.00',
        'total_tunjangan' => '0.00',
        'total_potongan' => '0.00',
        'gaji_bersih' => '5000000.00',
    ]);

    expect($slip->nomor)->toStartWith('SG-202503-');
    expect($slip->nomor)->toBe('SG-202503-0001');
});

it('increments nomor sequence within the same month', function () {
    // Use different pegawai per slip to avoid the (pegawai_id, tahun, bulan) unique constraint.
    $base = [
        'tahun' => 2025,
        'bulan' => 5,
        'gaji_pokok' => '3000000.00',
        'total_tunjangan' => '0.00',
        'total_potongan' => '0.00',
        'gaji_bersih' => '3000000.00',
        'nomor' => null,
    ];

    $first = SlipGaji::factory()->create(array_merge($base, ['pegawai_id' => Pegawai::factory()->create()->id]));
    $second = SlipGaji::factory()->create(array_merge($base, ['pegawai_id' => Pegawai::factory()->create()->id]));

    expect($first->nomor)->toBe('SG-202505-0001');
    expect($second->nomor)->toBe('SG-202505-0002');
});

it('resets numbering for a different month', function () {
    $pegawaiId = Pegawai::factory()->create()->id;
    $base = ['pegawai_id' => $pegawaiId, 'gaji_pokok' => '0.00', 'total_tunjangan' => '0.00', 'total_potongan' => '0.00', 'gaji_bersih' => '0.00'];

    $jan = SlipGaji::factory()->create(array_merge($base, ['tahun' => 2025, 'bulan' => 1, 'nomor' => null]));
    $feb = SlipGaji::factory()->create(array_merge($base, ['tahun' => 2025, 'bulan' => 2, 'nomor' => null]));

    expect($jan->nomor)->toBe('SG-202501-0001');
    expect($feb->nomor)->toBe('SG-202502-0001');
});

it('does not overwrite a manually supplied nomor', function () {
    $slip = SlipGaji::factory()->create([
        'nomor' => 'SG-MANUAL-0099',
        'tahun' => 2025,
        'bulan' => 6,
        'pegawai_id' => Pegawai::factory()->create()->id,
        'gaji_pokok' => '0.00',
        'total_tunjangan' => '0.00',
        'total_potongan' => '0.00',
        'gaji_bersih' => '0.00',
    ]);

    expect($slip->nomor)->toBe('SG-MANUAL-0099');
});
