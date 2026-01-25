<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanGajiStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    protected function getStats(): array
    {
        return [
            Stat::make('Total Pegawai', number_format($this->summary['total_pegawai'] ?? 0))
                ->description('Jumlah pegawai')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Gaji Pokok', 'Rp '.number_format($this->summary['total_gaji_pokok'] ?? 0, 0, ',', '.'))
                ->description('Total gaji pokok')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Tunjangan', 'Rp '.number_format($this->summary['total_tunjangan'] ?? 0, 0, ',', '.'))
                ->description('Total tunjangan')
                ->descriptionIcon('heroicon-m-plus-circle')
                ->color('success'),

            Stat::make('Potongan', 'Rp '.number_format($this->summary['total_potongan'] ?? 0, 0, ',', '.'))
                ->description('Total potongan')
                ->descriptionIcon('heroicon-m-minus-circle')
                ->color('danger'),

            Stat::make('Gaji Bersih', 'Rp '.number_format($this->summary['total_gaji_bersih'] ?? 0, 0, ',', '.'))
                ->description('Total gaji bersih')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
        ];
    }
}
