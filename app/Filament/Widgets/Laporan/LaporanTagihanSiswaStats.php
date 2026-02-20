<?php

namespace App\Filament\Widgets\Laporan;

use App\Filament\Pages\LaporanTagihanSiswa;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LaporanTagihanSiswaStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static bool $isDiscovered = false;

    protected function getTablePage(): string
    {
        return LaporanTagihanSiswa::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $jumlahTagihan = $query->count();
        $totalTagihan = $query->sum('total_tagihan');
        $totalTerbayar = $query->sum('total_terbayar');
        $totalSisa = $query->sum('sisa_tagihan');
        $belumBayar = (clone $query)->where('status', 'belum_bayar')->count();
        $sebagian = (clone $query)->where('status', 'sebagian')->count();
        $lunas = (clone $query)->where('status', 'lunas')->count();

        return [
            Stat::make('Jumlah Tagihan', number_format($jumlahTagihan))
                ->description("{$belumBayar} belum | {$sebagian} sebagian | {$lunas} lunas")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Total Tagihan', 'Rp '.number_format($totalTagihan, 0, ',', '.'))
                ->description('Nominal tagihan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Terbayar', 'Rp '.number_format($totalTerbayar, 0, ',', '.'))
                ->description('Sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Sisa Tagihan', 'Rp '.number_format($totalSisa, 0, ',', '.'))
                ->description('Belum terbayar')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
        ];
    }
}
