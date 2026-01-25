<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanTagihanSiswaStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    protected function getStats(): array
    {
        $belumBayar = $this->summary['belum_bayar'] ?? 0;
        $sebagian = $this->summary['sebagian'] ?? 0;
        $lunas = $this->summary['lunas'] ?? 0;

        return [
            Stat::make('Jumlah Tagihan', number_format($this->summary['jumlah_tagihan'] ?? 0))
                ->description("{$belumBayar} belum | {$sebagian} sebagian | {$lunas} lunas")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Total Tagihan', 'Rp '.number_format($this->summary['total_tagihan'] ?? 0, 0, ',', '.'))
                ->description('Nominal tagihan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Terbayar', 'Rp '.number_format($this->summary['total_terbayar'] ?? 0, 0, ',', '.'))
                ->description('Sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Sisa Tagihan', 'Rp '.number_format($this->summary['total_sisa'] ?? 0, 0, ',', '.'))
                ->description('Belum terbayar')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
        ];
    }
}
