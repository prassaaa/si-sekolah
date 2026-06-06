<?php

namespace App\Filament\Widgets;

use App\Models\SarprasBarang;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SarprasOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected ?string $heading = 'Ringkasan Sarana & Prasarana';

    protected function getStats(): array
    {
        $totalAset = SarprasBarang::count();

        $totalNilai = SarprasBarang::sum('harga_perolehan');

        $jumlahDipinjam = SarprasBarang::where('status', 'dipinjam')->count();

        $jumlahPerluPerbaikan = SarprasBarang::where(function ($q): void {
            $q->where('kondisi', 'rusak_berat')
                ->orWhere('kondisi', 'rusak_ringan')
                ->orWhere('status', 'perbaikan');
        })->count();

        return [
            Stat::make('Total Aset', number_format($totalAset))
                ->description('Seluruh barang terdaftar')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Total Nilai Aset', 'Rp '.number_format((float) $totalNilai, 0, ',', '.'))
                ->description('Jumlah harga perolehan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Sedang Dipinjam', number_format($jumlahDipinjam))
                ->description('Barang dalam status dipinjam')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color('warning'),

            Stat::make('Perlu Perbaikan', number_format($jumlahPerluPerbaikan))
                ->description('Barang rusak atau dalam perbaikan')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('danger'),
        ];
    }
}
