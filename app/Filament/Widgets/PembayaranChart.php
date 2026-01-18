<?php

namespace App\Filament\Widgets;

use App\Models\Pembayaran;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class PembayaranChart extends ChartWidget
{
    protected ?string $heading = 'Pembayaran per Bulan';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $labels[] = $date->format('M Y');

            $total = Pembayaran::where('status', 'berhasil')
                ->whereMonth('tanggal_bayar', $date->month)
                ->whereYear('tanggal_bayar', $date->year)
                ->sum('jumlah_bayar');

            $data[] = $total;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pembayaran (Rp)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
