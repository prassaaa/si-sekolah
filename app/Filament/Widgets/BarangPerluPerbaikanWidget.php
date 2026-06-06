<?php

namespace App\Filament\Widgets;

use App\Models\SarprasBarang;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BarangPerluPerbaikanWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 12;

    protected ?string $heading = 'Kondisi Barang';

    protected function getStats(): array
    {
        $rusakRingan = SarprasBarang::where('kondisi', 'rusak_ringan')
            ->whereNull('deleted_at')
            ->count();

        $rusakBerat = SarprasBarang::where('kondisi', 'rusak_berat')
            ->whereNull('deleted_at')
            ->count();

        $dalamPerbaikan = SarprasBarang::where('status', 'perbaikan')
            ->whereNull('deleted_at')
            ->count();

        $baik = SarprasBarang::where('kondisi', 'baik')
            ->whereNull('deleted_at')
            ->count();

        return [
            Stat::make('Kondisi Baik', number_format($baik))
                ->description('Barang dalam kondisi baik')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Rusak Ringan', number_format($rusakRingan))
                ->description('Perlu perhatian')
                ->descriptionIcon('heroicon-m-exclamation-circle')
                ->color('warning'),

            Stat::make('Rusak Berat', number_format($rusakBerat))
                ->description('Perlu perbaikan segera')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Dalam Perbaikan', number_format($dalamPerbaikan))
                ->description('Sedang diperbaiki')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('info'),
        ];
    }
}
