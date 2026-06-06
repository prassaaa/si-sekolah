<?php

use App\Filament\Pages\BukuBesar;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SaldoAwal;
use App\Models\TahunAjaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

uses(RefreshDatabase::class);

function buildLedger(array $filters): Collection
{
    $page = new BukuBesar;
    $method = new ReflectionMethod(BukuBesar::class, 'buildLedger');
    $method->setAccessible(true);

    return $method->invoke($page, $filters);
}

it('returns empty when no akun is selected', function () {
    expect(buildLedger([]))->toBeEmpty();
});

it('computes a running balance with opening and closing rows for a debit-normal account', function () {
    $tahunAjaran = TahunAjaran::factory()->create();
    $kas = Akun::factory()->create(['tipe' => 'aset', 'posisi_normal' => 'debit']);

    SaldoAwal::create([
        'akun_id' => $kas->id,
        'tahun_ajaran_id' => $tahunAjaran->id,
        'saldo' => 1000000,
        'tanggal' => '2025-12-31',
    ]);

    JurnalUmum::factory()->debit(500000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-01-05']);
    JurnalUmum::factory()->kredit(200000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-01-10']);

    $rows = buildLedger([
        'akun_id' => ['value' => $kas->id],
        'tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31'],
    ]);

    expect($rows->first()['keterangan'])->toBe('Saldo Awal')
        ->and($rows->first()['saldo'])->toBe('1000000.00')
        ->and($rows->last()['keterangan'])->toBe('Saldo Akhir')
        ->and($rows->last()['saldo'])->toBe('1300000.00');

    $movement = $rows->slice(1, 2)->values();
    expect($movement[0]['saldo'])->toBe('1500000.00')
        ->and($movement[1]['saldo'])->toBe('1300000.00');
});

it('honors posisi_normal kredit when computing running balance', function () {
    $modal = Akun::factory()->create(['tipe' => 'ekuitas', 'posisi_normal' => 'kredit']);

    JurnalUmum::factory()->kredit(800000)->create(['akun_id' => $modal->id, 'tanggal' => '2026-01-05']);
    JurnalUmum::factory()->debit(100000)->create(['akun_id' => $modal->id, 'tanggal' => '2026-01-10']);

    $rows = buildLedger([
        'akun_id' => ['value' => $modal->id],
        'tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31'],
    ]);

    expect($rows->last()['saldo'])->toBe('700000.00');
});
