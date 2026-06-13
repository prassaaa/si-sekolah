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
        $belumBayar = (clone $query)->where('status', 'belum_bayar')->count();
        $sebagian = (clone $query)->where('status', 'sebagian')->count();
        $lunas = (clone $query)->where('status', 'lunas')->count();
        $batal = (clone $query)->where('status', 'batal')->count();

        // Agregat nominal mengecualikan tagihan batal: tagihan yang dibatalkan
        // tidak menambah piutang maupun nilai tagihan (#79).
        $aktif = (clone $query)->where('status', '!=', 'batal');
        $totalTagihan = (clone $aktif)->sum('total_tagihan');
        $totalTerbayar = (clone $aktif)->sum('total_terbayar');
        $totalSisa = (clone $aktif)->sum('sisa_tagihan');

        return [
            Stat::make('Jumlah Tagihan', number_format($jumlahTagihan))
                ->description("{$belumBayar} belum | {$sebagian} sebagian | {$lunas} lunas | {$batal} batal")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Total Tagihan', 'Rp '.number_format($totalTagihan, 0, ',', '.'))
                ->description('Nominal tagihan (tidak termasuk batal)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Terbayar', 'Rp '.number_format($totalTerbayar, 0, ',', '.'))
                ->description('Sudah dibayar (tidak termasuk batal)')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Sisa Tagihan', 'Rp '.number_format($totalSisa, 0, ',', '.'))
                ->description('Belum terbayar (tidak termasuk batal)')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
        ];
    }
}
