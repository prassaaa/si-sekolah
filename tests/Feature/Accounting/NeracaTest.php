<?php

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\SaldoAwal;
use App\Models\TahunAjaran;
use App\Services\Accounting\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function neracaSaldo(string $tanggal): array
{
    $akunIds = Akun::query()->pluck('id')->all();

    return app(FinancialService::class)->saldoPerAkun($akunIds, $tanggal);
}

it('drives account balances from posisi_normal', function () {
    $kas = Akun::factory()->create(['tipe' => 'aset', 'posisi_normal' => 'debit']);
    $modal = Akun::factory()->create(['tipe' => 'ekuitas', 'posisi_normal' => 'kredit']);

    JurnalUmum::factory()->debit(1000000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-01-05']);
    JurnalUmum::factory()->kredit(200000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-01-06']);
    JurnalUmum::factory()->kredit(800000)->create(['akun_id' => $modal->id, 'tanggal' => '2026-01-05']);

    $saldo = neracaSaldo('2026-01-31');

    expect($saldo[$kas->id])->toBe('800000.00')
        ->and($saldo[$modal->id])->toBe('800000.00');
});

it('balances aset against liabilitas plus ekuitas', function () {
    $tahunAjaran = TahunAjaran::factory()->create();

    $kas = Akun::factory()->create(['tipe' => 'aset', 'posisi_normal' => 'debit']);
    $modal = Akun::factory()->create(['tipe' => 'ekuitas', 'posisi_normal' => 'kredit']);

    SaldoAwal::create([
        'akun_id' => $kas->id,
        'tahun_ajaran_id' => $tahunAjaran->id,
        'saldo' => 5000000,
        'tanggal' => '2026-01-01',
    ]);
    SaldoAwal::create([
        'akun_id' => $modal->id,
        'tahun_ajaran_id' => $tahunAjaran->id,
        'saldo' => 5000000,
        'tanggal' => '2026-01-01',
    ]);

    $saldo = neracaSaldo('2026-01-31');

    $totalAset = $saldo[$kas->id];
    $totalEkuitas = $saldo[$modal->id];

    expect(bccomp($totalAset, $totalEkuitas, 2))->toBe(0);
});

it('excludes soft-deleted journal rows from balances', function () {
    $kas = Akun::factory()->create(['tipe' => 'aset', 'posisi_normal' => 'debit']);

    $row = JurnalUmum::factory()->debit(1000000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-01-05']);
    JurnalUmum::factory()->debit(500000)->create(['akun_id' => $kas->id, 'tanggal' => '2026-01-06']);

    $row->delete();

    $saldo = neracaSaldo('2026-01-31');

    expect($saldo[$kas->id])->toBe('500000.00');
});
