<?php

use App\Filament\Pages\LabaRugi;
use App\Filament\Pages\PerubahanModal;
use App\Models\Akun;
use App\Models\JurnalUmum;
use App\Models\User;
use App\Services\Accounting\FinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());

    $pendapatan = Akun::factory()->pendapatan()->create();
    $beban = Akun::factory()->beban()->create();

    JurnalUmum::factory()->kredit(2000000)->create(['akun_id' => $pendapatan->id, 'tanggal' => '2026-01-10']);
    JurnalUmum::factory()->debit(750000)->create(['akun_id' => $beban->id, 'tanggal' => '2026-01-12']);

    $this->expectedNetIncome = app(FinancialService::class)->netIncome('2026-01-01', '2026-01-31');
});

it('LabaRugi reports the same net income as the service', function () {
    $component = Livewire::test(LabaRugi::class)
        ->set('tableFilters', [
            'tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31'],
        ]);

    $component->instance()->getTableRecords();

    expect((string) number_format($component->get('labaRugi'), 2, '.', ''))
        ->toBe($this->expectedNetIncome);
});

it('PerubahanModal uses the same net income figure for laba/rugi periode', function () {
    $component = Livewire::test(PerubahanModal::class)
        ->set('tableFilters', [
            'tanggal' => ['tanggal_mulai' => '2026-01-01', 'tanggal_akhir' => '2026-01-31'],
        ]);

    $records = collect($component->instance()->getTableRecords());
    $labaRugiRow = $records->firstWhere('uraian', 'Laba/Rugi Periode');

    expect($labaRugiRow['nominal'])->toBe($this->expectedNetIncome);
});

it('the service is the single source of truth', function () {
    expect($this->expectedNetIncome)->toBe('1250000.00');
});
