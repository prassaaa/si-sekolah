<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanPembayaranStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    protected function getStats(): array
    {
        return [
            Stat::make('Total Tagihan', 'Rp '.number_format($this->summary['total_tagihan'] ?? 0, 0, ',', '.'))
                ->description('Total tagihan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Total Terbayar', 'Rp '.number_format($this->summary['total_terbayar'] ?? 0, 0, ',', '.'))
                ->description('Sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Sisa Tagihan', 'Rp '.number_format($this->summary['total_sisa'] ?? 0, 0, ',', '.'))
                ->description('Belum terbayar')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Persentase', ($this->summary['persentase'] ?? 0).'%')
                ->description('Tingkat pembayaran')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('primary'),
        ];
    }
}
