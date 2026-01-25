<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanTahfidzStats extends StatsOverviewWidget
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
                ->color('primary'),

            Stat::make('Total Setoran', number_format($this->summary['total_setoran'] ?? 0))
                ->description('Jumlah setoran')
                ->descriptionIcon('heroicon-m-book-open')
                ->color('success'),

            Stat::make("Total Muroja'ah", number_format($this->summary['total_murojaah'] ?? 0))
                ->description("Jumlah muroja'ah")
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color('info'),

            Stat::make('Total Ayat', number_format($this->summary['total_ayat'] ?? 0))
                ->description('Jumlah ayat')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Rata-rata Nilai', number_format($this->summary['rata_rata_nilai'] ?? 0, 1))
                ->description('Nilai rata-rata')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('gray'),
        ];
    }
}
