<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanUnitPosStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    #[Reactive]
    public ?string $unitPosNama = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Unit POS', $this->unitPosNama ?? 'Semua Unit')
                ->description('Unit dipilih')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('primary'),

            Stat::make('Total Unit', number_format($this->summary['total_unit'] ?? 0))
                ->description('Jumlah unit')
                ->descriptionIcon('heroicon-m-squares-2x2')
                ->color('gray'),

            Stat::make('Total Transaksi', number_format($this->summary['total_transaksi'] ?? 0))
                ->description('Jumlah transaksi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Total Nominal', 'Rp '.number_format($this->summary['total_nominal'] ?? 0, 0, ',', '.'))
                ->description('Nominal transaksi')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
