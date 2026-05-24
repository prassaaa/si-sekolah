<?php

namespace App\Filament\Widgets;

use App\Models\PresensiHarian;
use App\Models\Siswa;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PresensiHariIniWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $hadir = PresensiHarian::hariIni()->byStatus('hadir')->count();
        $terlambat = PresensiHarian::hariIni()->byStatus('terlambat')->count();
        $izin = PresensiHarian::hariIni()->byStatus('izin')->count();
        $sakit = PresensiHarian::hariIni()->byStatus('sakit')->count();
        $alpha = PresensiHarian::hariIni()->byStatus('alpha')->count();

        $totalSiswaAktif = Siswa::where('status', 'aktif')->count();
        $sudahTerdata = $hadir + $terlambat + $izin + $sakit + $alpha;
        $belumTap = max(0, $totalSiswaAktif - $sudahTerdata);

        return [
            Stat::make('Hadir', $hadir + $terlambat)
                ->description($terlambat.' terlambat')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Izin / Sakit', $izin + $sakit)
                ->description("$izin izin, $sakit sakit")
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),

            Stat::make('Alpha', $alpha)
                ->description('Tanpa keterangan')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Belum Tap', $belumTap)
                ->description("$totalSiswaAktif total siswa aktif")
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
        ];
    }
}
