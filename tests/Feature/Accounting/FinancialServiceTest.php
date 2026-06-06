<?php

use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Services\Accounting\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(FinancialService::class);
});

it('computes net income as pendapatan minus beban from the ledger', function () {
    $pendapatan = Akun::factory()->pendapatan()->create();
    $beban = Akun::factory()->beban()->create();

    JurnalUmum::factory()->kredit(1000000)->create([
        'akun_id' => $pendapatan->id,
        'tanggal' => '2026-01-15',
    ]);
    JurnalUmum::factory()->debit(400000)->create([
        'akun_id' => $beban->id,
        'tanggal' => '2026-01-20',
    ]);

    expect($this->service->totalPendapatan('2026-01-01', '2026-01-31'))->toBe('1000000.00')
        ->and($this->service->totalBeban('2026-01-01', '2026-01-31'))->toBe('400000.00')
        ->and($this->service->netIncome('2026-01-01', '2026-01-31'))->toBe('600000.00');
});

it('honors the period boundaries with start and end of day', function () {
    $pendapatan = Akun::factory()->pendapatan()->create();

    JurnalUmum::factory()->kredit(500000)->create([
        'akun_id' => $pendapatan->id,
        'tanggal' => '2026-01-31',
    ]);
    JurnalUmum::factory()->kredit(999999)->create([
        'akun_id' => $pendapatan->id,
        'tanggal' => '2026-02-01',
    ]);

    expect($this->service->totalPendapatan('2026-01-01', '2026-01-31'))->toBe('500000.00');
});

it('returns zero when no accounts of a tipe exist', function () {
    expect($this->service->totalPendapatan('2026-01-01', '2026-01-31'))->toBe('0.00')
        ->and($this->service->netIncome('2026-01-01', '2026-01-31'))->toBe('0.00');
});
