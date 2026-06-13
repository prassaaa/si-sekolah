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
                ->description('Total tagihan (tidak termasuk batal)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Terbayar (periode ini)', 'Rp '.number_format($this->summary['terbayar_periode'] ?? 0, 0, ',', '.'))
                ->description('Pembayaran berhasil pada rentang tanggal')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Sisa Tagihan', 'Rp '.number_format($this->summary['total_sisa'] ?? 0, 0, ',', '.'))
                ->description('Sisa tagihan riil (posisi terkini)')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Persentase Lunas', ($this->summary['persentase'] ?? 0).'%')
                ->description('Tagihan terbayar dari total')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->color('primary'),
        ];
    }
}
