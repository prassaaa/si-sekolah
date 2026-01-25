<?php

namespace App\Filament\Widgets\Laporan;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\Reactive;

class LaporanSiswaStats extends StatsOverviewWidget
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

            Stat::make('Laki-laki', number_format($this->summary['siswa_per_jenis_kelamin']['L'] ?? 0))
                ->description('Siswa laki-laki')
                ->descriptionIcon('heroicon-m-user')
                ->color('info'),

            Stat::make('Perempuan', number_format($this->summary['siswa_per_jenis_kelamin']['P'] ?? 0))
                ->description('Siswa perempuan')
                ->descriptionIcon('heroicon-m-user')
                ->color('pink'),
        ];
    }
}
