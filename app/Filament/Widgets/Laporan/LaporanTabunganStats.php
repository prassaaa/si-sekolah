<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanTabunganStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    protected function getStats(): array
    {
        return [
            Stat::make('Total Siswa', number_format($this->summary['total_siswa'] ?? 0))
                ->description('Jumlah siswa')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Total Setoran', 'Rp '.number_format($this->summary['total_setor'] ?? 0, 0, ',', '.'))
                ->description('Total setoran')
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),

            Stat::make('Total Penarikan', 'Rp '.number_format($this->summary['total_tarik'] ?? 0, 0, ',', '.'))
                ->description('Total penarikan')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('danger'),

            Stat::make('Total Saldo', 'Rp '.number_format($this->summary['total_saldo'] ?? 0, 0, ',', '.'))
                ->description('Saldo tabungan')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
        ];
    }
}
