<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanPembayaranPerTanggalStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    protected function getStats(): array
    {
        return [
            Stat::make('Total Transaksi', number_format($this->summary['total_transaksi'] ?? 0))
                ->description('Jumlah transaksi')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Tunai', 'Rp '.number_format($this->summary['total_tunai'] ?? 0, 0, ',', '.'))
                ->description('Pembayaran tunai')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Transfer', 'Rp '.number_format($this->summary['total_transfer'] ?? 0, 0, ',', '.'))
                ->description('Pembayaran transfer')
                ->descriptionIcon('heroicon-m-building-library')
                ->color('info'),

            Stat::make('Lainnya', 'Rp '.number_format($this->summary['total_lainnya'] ?? 0, 0, ',', '.'))
                ->description('Metode lainnya')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),

            Stat::make('Grand Total', 'Rp '.number_format($this->summary['grand_total'] ?? 0, 0, ',', '.'))
                ->description('Total keseluruhan')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary'),
        ];
    }
}
