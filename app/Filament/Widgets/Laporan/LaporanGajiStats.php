<?php

namespace App\Filament\Widgets\Laporan;

use App\Filament\Pages\LaporanGaji;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LaporanGajiStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static bool $isDiscovered = false;

    protected function getTablePage(): string
    {
        return LaporanGaji::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $totalPegawai = $query->count();
        $totalGajiPokok = $query->sum('gaji_pokok');
        $totalTunjangan = $query->sum('total_tunjangan');
        $totalPotongan = $query->sum('total_potongan');
        $totalGajiBersih = $query->sum('gaji_bersih');

        return [
            Stat::make('Total Pegawai', number_format($totalPegawai))
                ->description('Jumlah pegawai')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Gaji Pokok', 'Rp '.number_format($totalGajiPokok, 0, ',', '.'))
                ->description('Total gaji pokok')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Tunjangan', 'Rp '.number_format($totalTunjangan, 0, ',', '.'))
                ->description('Total tunjangan')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('success'),

            Stat::make('Potongan', 'Rp '.number_format($totalPotongan, 0, ',', '.'))
                ->description('Total potongan')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('danger'),

            Stat::make('Gaji Bersih', 'Rp '.number_format($totalGajiBersih, 0, ',', '.'))
                ->description('Total gaji bersih')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
        ];
    }
}
