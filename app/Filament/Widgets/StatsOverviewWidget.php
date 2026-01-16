<?php

namespace App\Filament\Widgets;

use App\Models\Pegawai;
use App\Models\Pembayaran;
use App\Models\Siswa;
use App\Models\TagihanSiswa;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalSiswa = Siswa::count();
        $siswaAktif = Siswa::where('status', 'aktif')->count();
        $totalPegawai = Pegawai::count();
        $pegawaiAktif = Pegawai::where('is_active', true)->count();

        $totalTagihan = TagihanSiswa::where('status', '!=', 'batal')->sum('total_tagihan');
        $totalTerbayar = TagihanSiswa::where('status', '!=', 'batal')->sum('total_terbayar');
        $sisaTagihan = $totalTagihan - $totalTerbayar;

        $pembayaranBulanIni = Pembayaran::where('status', 'berhasil')
            ->whereMonth('tanggal_bayar', now()->month)
            ->whereYear('tanggal_bayar', now()->year)
            ->sum('jumlah_bayar');

        return [
            Stat::make('Total Siswa', number_format($totalSiswa))
                ->description("$siswaAktif siswa aktif")
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('Total Pegawai', number_format($totalPegawai))
                ->description("$pegawaiAktif pegawai aktif")
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),

            Stat::make('Total Tagihan', 'Rp '.number_format($totalTagihan, 0, ',', '.'))
                ->description('Rp '.number_format($totalTerbayar, 0, ',', '.').' terbayar')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),

            Stat::make('Pembayaran Bulan Ini', 'Rp '.number_format($pembayaranBulanIni, 0, ',', '.'))
                ->description(now()->format('F Y'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
        ];
    }
}
