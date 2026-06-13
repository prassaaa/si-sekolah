<?php

namespace App\Filament\Widgets\Laporan;

use App\Services\Accounting\FinancialService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class TrenKeuanganChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Tren Pendapatan vs Beban (12 Bulan Terakhir)';

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = null;

    /**
     * Widget hanya ditampilkan kepada pengguna yang memiliki permission
     * View:DashboardKeuangan.
     */
    public static function canView(): bool
    {
        return auth()->user()?->can('View:DashboardKeuangan') ?? false;
    }

    protected function getData(): array
    {
        $financial = app(FinancialService::class);

        $pendapatanData = [];
        $bebanData = [];
        $labels = [];

        for ($i = 11; $i >= 0; $i--) {
            $bulan = Carbon::now()->startOfMonth()->subMonths($i);
            $labels[] = $bulan->translatedFormat('M Y');

            $start = $bulan->copy()->startOfMonth();
            $end = $bulan->copy()->endOfMonth();

            $pendapatanData[] = (float) $financial->totalPendapatan($start, $end);
            $bebanData[] = (float) $financial->totalBeban($start, $end);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $pendapatanData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.2)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Beban (Rp)',
                    'data' => $bebanData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.2)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
