<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanPembayaranPerKelasStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    #[Reactive]
    public array $summary = [];

    #[Reactive]
    public ?string $kelasNama = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Kelas', $this->kelasNama ?? '-')
                ->description('Kelas dipilih')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Total Siswa', number_format($this->summary['total_siswa'] ?? 0))
                ->description('Jumlah siswa')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Total Tagihan', 'Rp '.number_format($this->summary['total_tagihan'] ?? 0, 0, ',', '.'))
                ->description('Nominal tagihan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('gray'),

            Stat::make('Terbayar', 'Rp '.number_format($this->summary['total_terbayar'] ?? 0, 0, ',', '.'))
                ->description('Sudah dibayar')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Lunas', number_format($this->summary['lunas'] ?? 0))
                ->description('Tagihan lunas')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Belum Lunas', number_format($this->summary['belum_lunas'] ?? 0))
                ->description('Belum terbayar')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),
        ];
    }
}
