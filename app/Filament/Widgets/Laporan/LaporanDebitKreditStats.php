<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanDebitKreditStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    protected function getStats(): array
    {
        $selisih = $this->summary['selisih'] ?? 0;
        $selisihColor = $selisih >= 0 ? 'success' : 'danger';

        return [
            Stat::make('Transaksi Masuk', number_format($this->summary['jml_masuk'] ?? 0))
                ->description('Jumlah kas masuk')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),

            Stat::make('Transaksi Keluar', number_format($this->summary['jml_keluar'] ?? 0))
                ->description('Jumlah kas keluar')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('danger'),

            Stat::make('Total Kas Masuk', 'Rp '.number_format($this->summary['total_masuk'] ?? 0, 0, ',', '.'))
                ->description('Nominal kas masuk')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('success'),

            Stat::make('Total Kas Keluar', 'Rp '.number_format($this->summary['total_keluar'] ?? 0, 0, ',', '.'))
                ->description('Nominal kas keluar')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('danger'),

            Stat::make('Selisih', 'Rp '.number_format($selisih, 0, ',', '.'))
                ->description('Selisih kas')
                ->descriptionIcon('heroicon-m-scale')
                ->color($selisihColor),
        ];
    }
}
