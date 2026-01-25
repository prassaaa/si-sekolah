<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanJurnalStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    protected function getStats(): array
    {
        return [
            Stat::make('Total Transaksi', number_format($this->summary['total_transaksi'] ?? 0))
                ->description('Jumlah transaksi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Total Debit', 'Rp '.number_format($this->summary['total_debit'] ?? 0, 0, ',', '.'))
                ->description('Sisi debit')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Kredit', 'Rp '.number_format($this->summary['total_kredit'] ?? 0, 0, ',', '.'))
                ->description('Sisi kredit')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}
