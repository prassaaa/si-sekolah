<?php

namespace App\Filament\Widgets\Laporan;

use App\Filament\Pages\LaporanTunggakan;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class LaporanTunggakanStats extends StatsOverviewWidget
{
    use InteractsWithPageTable;

    protected static bool $isDiscovered = false;

    protected function getTablePage(): string
    {
        return LaporanTunggakan::class;
    }

    protected function getStats(): array
    {
        $query = $this->getPageTableQuery();

        $jumlahSiswa = (clone $query)->count();
        $totalSisa = (clone $query)->sum('sisa_tagihan');

        $now = Carbon::now();

        $bucket1 = (clone $query)
            ->whereBetween('tanggal_jatuh_tempo', [$now->copy()->subDays(30), $now->copy()->subDay()])
            ->count();

        $bucket2 = (clone $query)
            ->whereBetween('tanggal_jatuh_tempo', [$now->copy()->subDays(60), $now->copy()->subDays(31)])
            ->count();

        $bucket3 = (clone $query)
            ->whereBetween('tanggal_jatuh_tempo', [$now->copy()->subDays(90), $now->copy()->subDays(61)])
            ->count();

        $bucket4 = (clone $query)
            ->where('tanggal_jatuh_tempo', '<', $now->copy()->subDays(90))
            ->count();

        return [
            Stat::make('Siswa Menunggak', number_format($jumlahSiswa))
                ->description('Tagihan lewat jatuh tempo')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('danger'),

            Stat::make('Total Tunggakan', 'Rp '.number_format($totalSisa, 0, ',', '.'))
                ->description('Sisa tagihan belum terbayar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),

            Stat::make('Bucket Umur', "{$bucket1} | {$bucket2} | {$bucket3} | {$bucket4}")
                ->description('1-30 | 31-60 | 61-90 | >90 hari')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
