<?php

namespace App\Filament\Widgets\Laporan;

use App\Models\Akun;
use App\Services\Accounting\FinancialService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SaldoKasBankWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Saldo Kas & Bank';

    /**
     * Widget hanya ditampilkan kepada pengguna yang memiliki permission
     * View:DashboardKeuangan.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('View:DashboardKeuangan') ?? false;
    }

    protected function getStats(): array
    {
        /** Kode akun kas/bank sesuai AkunSeeder: 1-1001 s/d 1-1005. */
        $kodeKasBank = ['1-1001', '1-1002', '1-1003', '1-1004', '1-1005'];

        $akuns = Akun::whereIn('kode', $kodeKasBank)
            ->orderBy('kode')
            ->get(['id', 'kode', 'nama']);

        if ($akuns->isEmpty()) {
            return [
                Stat::make('Saldo Kas & Bank', 'Tidak ada data akun')
                    ->color('gray'),
            ];
        }

        $financial = app(FinancialService::class);
        $perTanggal = now()->toDateString();

        $saldoPerAkun = $financial->saldoPerAkun(
            $akuns->pluck('id')->all(),
            $perTanggal,
        );

        $stats = [];
        $totalSaldo = '0';

        foreach ($akuns as $akun) {
            $saldo = $saldoPerAkun[$akun->id] ?? '0';
            $totalSaldo = bcadd($totalSaldo, $saldo, 2);

            $stats[] = Stat::make(
                $akun->nama,
                'Rp '.number_format((float) $saldo, 0, ',', '.'),
            )
                ->description('Per '.now()->translatedFormat('d M Y'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color((float) $saldo > 0 ? 'success' : 'gray');
        }

        /** Tambahkan baris total di paling akhir. */
        array_unshift($stats, Stat::make(
            'Total Kas & Bank',
            'Rp '.number_format((float) $totalSaldo, 0, ',', '.'),
        )
            ->description('Gabungan seluruh akun kas & bank')
            ->descriptionIcon('heroicon-m-building-library')
            ->color((float) $totalSaldo > 0 ? 'info' : 'gray'));

        return $stats;
    }
}
