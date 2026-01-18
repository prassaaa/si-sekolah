<?php

namespace App\Filament\Widgets;

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Pembayaran;
use App\Models\TagihanSiswa;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Ringkasan Keuangan';

    protected function getStats(): array
    {
        $bulanIni = now()->startOfMonth();

        $kasMasuk = KasMasuk::where('tanggal', '>=', $bulanIni)->sum('nominal');
        $kasKeluar = KasKeluar::where('tanggal', '>=', $bulanIni)->sum('nominal');
        $pembayaranBulanIni = Pembayaran::where('tanggal_bayar', '>=', $bulanIni)->sum('jumlah_bayar');
        $tagihanBelumLunas = TagihanSiswa::where('status', 'belum_lunas')->sum('sisa_tagihan');

        $saldoBersih = $kasMasuk - $kasKeluar;

        return [
            Stat::make('Kas Masuk Bulan Ini', 'Rp ' . number_format($kasMasuk, 0, ',', '.'))
                ->description('Total pemasukan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([50000, 75000, 100000, 125000, $kasMasuk]),

            Stat::make('Kas Keluar Bulan Ini', 'Rp ' . number_format($kasKeluar, 0, ',', '.'))
                ->description('Total pengeluaran')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart([30000, 45000, 60000, 75000, $kasKeluar]),

            Stat::make('Saldo Bersih', 'Rp ' . number_format($saldoBersih, 0, ',', '.'))
                ->description($saldoBersih >= 0 ? 'Surplus' : 'Defisit')
                ->descriptionIcon($saldoBersih >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-x-circle')
                ->color($saldoBersih >= 0 ? 'success' : 'danger'),

            Stat::make('Pembayaran Siswa', 'Rp ' . number_format($pembayaranBulanIni, 0, ',', '.'))
                ->description('Bulan ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info')
                ->url(route('filament.auth.resources.pembayarans.index')),

            Stat::make('Tagihan Belum Lunas', 'Rp ' . number_format($tagihanBelumLunas, 0, ',', '.'))
                ->description('Total piutang')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning')
                ->url(route('filament.auth.resources.tagihan-siswas.index')),
        ];
    }
}
