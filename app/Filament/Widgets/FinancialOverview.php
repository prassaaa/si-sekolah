<?php

namespace App\Filament\Widgets;

use App\Models\KasKeluar;
use App\Models\KasMasuk;
use App\Models\Pembayaran;
use App\Models\TagihanSiswa;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinancialOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected ?string $heading = 'Ringkasan Keuangan';

    protected function getStats(): array
    {
        $bulanIni = now()->startOfMonth();

        $kasMasuk = KasMasuk::where('tanggal', '>=', $bulanIni)->sum('nominal');
        $kasKeluar = KasKeluar::where('tanggal', '>=', $bulanIni)->sum(
            'nominal',
        );
        $pembayaranBulanIni = Pembayaran::where(
            'tanggal_bayar',
            '>=',
            $bulanIni,
        )
            ->where('status', 'berhasil')
            ->sum('jumlah_bayar');
        $tagihanBelumLunas = TagihanSiswa::whereIn('status', [
            'belum_bayar',
            'sebagian',
        ])->sum('sisa_tagihan');

        $saldoBersih = $kasMasuk - $kasKeluar;

        return [
            Stat::make(
                'Kas Masuk Bulan Ini',
                'Rp '.number_format($kasMasuk, 0, ',', '.'),
            )
                ->description('Total pemasukan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart(static::getMonthlyTrend('kas_masuks', 'nominal')),

            Stat::make(
                'Kas Keluar Bulan Ini',
                'Rp '.number_format($kasKeluar, 0, ',', '.'),
            )
                ->description('Total pengeluaran')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('danger')
                ->chart(static::getMonthlyTrend('kas_keluars', 'nominal')),

            Stat::make(
                'Saldo Bersih',
                'Rp '.number_format($saldoBersih, 0, ',', '.'),
            )
                ->description($saldoBersih >= 0 ? 'Surplus' : 'Defisit')
                ->descriptionIcon(
                    $saldoBersih >= 0
                        ? 'heroicon-m-check-circle'
                        : 'heroicon-m-x-circle',
                )
                ->color($saldoBersih >= 0 ? 'success' : 'danger'),

            Stat::make(
                'Pembayaran Siswa',
                'Rp '.number_format($pembayaranBulanIni, 0, ',', '.'),
            )
                ->description('Bulan ini')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info')
                ->url(route('filament.auth.resources.pembayarans.index')),

            Stat::make(
                'Tagihan Belum Lunas',
                'Rp '.number_format($tagihanBelumLunas, 0, ',', '.'),
            )
                ->description('Total piutang')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning')
                ->url(route('filament.auth.resources.tagihan-siswas.index')),
        ];
    }

    /**
     * @return array<int, float>
     */
    private static function getMonthlyTrend(
        string $table,
        string $column,
    ): array {
        $results = DB::table($table)
            ->selectRaw(
                'MONTH(tanggal) as bulan, SUM('.$column.') as total',
            )
            ->whereYear('tanggal', now()->year)
            ->where('tanggal', '<=', now())
            ->groupByRaw('MONTH(tanggal)')
            ->orderBy('bulan')
            ->pluck('total', 'bulan');

        $trend = [];
        $startMonth = max(1, now()->month - 4);
        for ($i = $startMonth; $i <= now()->month; $i++) {
            $trend[] = (float) ($results[$i] ?? 0);
        }

        return $trend;
    }
}
