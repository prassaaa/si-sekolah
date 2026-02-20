<?php

namespace App\Filament\Widgets\Laporan;

use App\Filament\Pages\LaporanJurnal;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LaporanJurnalStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static bool $isDiscovered = false;

    protected function getTablePage(): string
    {
        return LaporanJurnal::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totalTransaksi = $query->count();
        $totalDebit = $query->sum('debit');
        $totalKredit = $query->sum('kredit');

        return [
            Stat::make('Total Transaksi', number_format($totalTransaksi))
                ->description('Jumlah transaksi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Total Debit', 'Rp '.number_format($totalDebit, 0, ',', '.'))
                ->description('Sisi debit')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Kredit', 'Rp '.number_format($totalKredit, 0, ',', '.'))
                ->description('Sisi kredit')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger'),
        ];
    }
}
