<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanKeuanganStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    protected function getStats(): array
    {
        return [
            Stat::make('Tagihan Terbit', 'Rp '.number_format($this->summary['total_tagihan'] ?? 0, 0, ',', '.'))
                ->description('Tagihan terbit periode ini (tgl tagihan)')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Pembayaran Diterima', 'Rp '.number_format($this->summary['total_pembayaran'] ?? 0, 0, ',', '.'))
                ->description('Pembayaran diterima periode ini (tgl bayar)')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Tagihan Lunas', number_format($this->summary['tagihan_lunas'] ?? 0))
                ->description('Tagihan terbayar')
                ->descriptionIcon('heroicon-m-document-check')
                ->color('info'),

            Stat::make('Belum Lunas', number_format($this->summary['tagihan_belum_lunas'] ?? 0))
                ->description('Tagihan tertunggak')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }
}
