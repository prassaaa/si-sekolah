<?php

namespace App\Filament\Widgets;

use App\Models\SarprasBarang;
use App\Services\Sarpras\PenyusutanService;
use Filament\Widgets\StatsOverviewWidget as BaseStatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PenyusutanOverviewWidget extends BaseStatsOverviewWidget
{
    protected static ?int $sort = 11;

    protected ?string $heading = 'Penyusutan Aset';

    protected function getStats(): array
    {
        $service = app(PenyusutanService::class);
        $sampai = Carbon::now();

        $totalPerolehan = '0.00';
        $totalAkumulasi = '0.00';
        $totalNilaiBuku = '0.00';

        SarprasBarang::query()
            ->with('kategori')
            ->where('tipe', 'aset')
            ->chunk(200, function ($barangs) use ($service, $sampai, &$totalPerolehan, &$totalAkumulasi, &$totalNilaiBuku): void {
                foreach ($barangs as $barang) {
                    $totalPerolehan = bcadd($totalPerolehan, (string) $barang->harga_perolehan, 2);
                    $totalAkumulasi = bcadd($totalAkumulasi, $service->akumulasiSampai($barang, $sampai), 2);
                    $totalNilaiBuku = bcadd($totalNilaiBuku, $service->nilaiBuku($barang, $sampai), 2);
                }
            });

        return [
            Stat::make('Total Nilai Perolehan', $this->rupiah($totalPerolehan))
                ->description('Seluruh aset')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),

            Stat::make('Total Akumulasi Penyusutan', $this->rupiah($totalAkumulasi))
                ->description('Sampai hari ini')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            Stat::make('Total Nilai Buku', $this->rupiah($totalNilaiBuku))
                ->description('Perolehan - akumulasi')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('success'),
        ];
    }

    private function rupiah(string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }
}
