<?php

namespace App\Filament\Widgets;

use App\Models\SarprasPeminjaman;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PeminjamanAktifWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 11;

    protected ?string $heading = 'Peminjaman Aktif';

    protected function getStats(): array
    {
        $dipinjam = SarprasPeminjaman::where('status', 'dipinjam')->count();

        $terlambat = SarprasPeminjaman::where('status', 'dipinjam')
            ->whereDate('tanggal_harus_kembali', '<', now()->toDateString())
            ->count();

        $dikembalikanBulanIni = SarprasPeminjaman::where('status', 'dikembalikan')
            ->whereMonth('tanggal_kembali', now()->month)
            ->whereYear('tanggal_kembali', now()->year)
            ->count();

        $hilang = SarprasPeminjaman::where('status', 'hilang')->count();

        return [
            Stat::make('Sedang Dipinjam', number_format($dipinjam))
                ->description('Total peminjaman aktif')
                ->descriptionIcon('heroicon-m-arrow-up-tray')
                ->color('warning'),

            Stat::make('Terlambat Kembali', number_format($terlambat))
                ->description('Melewati batas pengembalian')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),

            Stat::make('Dikembalikan Bulan Ini', number_format($dikembalikanBulanIni))
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-arrow-down-tray')
                ->color('success'),

            Stat::make('Barang Hilang', number_format($hilang))
                ->description('Belum ditemukan')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
